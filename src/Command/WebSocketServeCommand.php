<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:websocket:serve',
    description: 'Run a lightweight WebSocket server (text frames + broadcast).',
)]
class WebSocketServeCommand extends Command
{
    /** @var array<int, resource> */
    private array $clients = [];

    protected function configure(): void
    {
        $this
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Bind host', '127.0.0.1')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'Bind port', '8081');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $host = (string) $input->getOption('host');
        $port = (int) $input->getOption('port');

        $server = @stream_socket_server(sprintf('tcp://%s:%d', $host, $port), $errno, $errstr);
        if (!$server) {
            $io->error(sprintf('Unable to start server: %s (%d)', $errstr, $errno));
            return Command::FAILURE;
        }

        stream_set_blocking($server, false);
        $io->success(sprintf('WebSocket listening on ws://%s:%d', $host, $port));
        $io->writeln('Press Ctrl+C to stop.');

        while (true) {
            $read = array_merge([$server], $this->clients);
            $write = null;
            $except = null;

            if (@stream_select($read, $write, $except, 0, 200000) === false) {
                continue;
            }

            foreach ($read as $socket) {
                if ($socket === $server) {
                    $this->acceptClient($server, $io);
                    continue;
                }

                $data = @fread($socket, 8192);
                if ($data === '' || $data === false) {
                    $this->removeClient($socket, $io);
                    continue;
                }

                $decoded = $this->decodeFrame($data);
                if ($decoded === null) {
                    continue;
                }

                $payload = trim($decoded);
                if ($payload === '') {
                    continue;
                }

                $message = sprintf('[%s] %s', (new \DateTimeImmutable())->format('H:i:s'), $payload);
                $this->broadcast($message);
            }
        }
    }

    /** @param resource $server */
    private function acceptClient($server, SymfonyStyle $io): void
    {
        $client = @stream_socket_accept($server, 0);
        if (!$client) {
            return;
        }

        stream_set_blocking($client, false);
        $request = @fread($client, 8192);

        if (!is_string($request) || !$this->performHandshake($client, $request)) {
            @fclose($client);
            return;
        }

        $this->clients[] = $client;
        $io->writeln(sprintf('Client connected. Active clients: %d', count($this->clients)));
    }

    /** @param resource $client */
    private function removeClient($client, SymfonyStyle $io): void
    {
        foreach ($this->clients as $index => $socket) {
            if ($socket === $client) {
                @fclose($socket);
                unset($this->clients[$index]);
                $this->clients = array_values($this->clients);
                $io->writeln(sprintf('Client disconnected. Active clients: %d', count($this->clients)));
                break;
            }
        }
    }

    /** @param resource $client */
    private function performHandshake($client, string $request): bool
    {
        if (!preg_match('/Sec-WebSocket-Key:\\s*(.*)\\r\\n/i', $request, $matches)) {
            return false;
        }

        $key = trim($matches[1]);
        $accept = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

        $upgrade = "HTTP/1.1 101 Switching Protocols\r\n";
        $upgrade .= "Upgrade: websocket\r\n";
        $upgrade .= "Connection: Upgrade\r\n";
        $upgrade .= "Sec-WebSocket-Accept: {$accept}\r\n\r\n";

        return @fwrite($client, $upgrade) !== false;
    }

    private function decodeFrame(string $data): ?string
    {
        if (strlen($data) < 6) {
            return null;
        }

        $bytes = array_values(unpack('C*', $data));
        $length = $bytes[1] & 127;
        $maskOffset = 2;

        if ($length === 126) {
            if (strlen($data) < 8) {
                return null;
            }
            $length = ($bytes[2] << 8) + $bytes[3];
            $maskOffset = 4;
        } elseif ($length === 127) {
            return null;
        }

        $mask = array_slice($bytes, $maskOffset, 4);
        $payloadOffset = $maskOffset + 4;
        $payloadBytes = array_slice($bytes, $payloadOffset, $length);

        $decoded = '';
        foreach ($payloadBytes as $i => $byte) {
            $decoded .= chr($byte ^ $mask[$i % 4]);
        }

        return $decoded;
    }

    private function encodeFrame(string $payload): string
    {
        $length = strlen($payload);

        if ($length <= 125) {
            return chr(129) . chr($length) . $payload;
        }

        if ($length <= 65535) {
            return chr(129) . chr(126) . pack('n', $length) . $payload;
        }

        return '';
    }

    private function broadcast(string $message): void
    {
        $frame = $this->encodeFrame($message);
        if ($frame === '') {
            return;
        }

        foreach ($this->clients as $socket) {
            @fwrite($socket, $frame);
        }
    }
}
