const http = require("http");
const { Server } = require("socket.io");

const PORT = Number(process.env.SOCKET_IO_PORT || 3001);
const BROADCAST_SECRET = String(process.env.APP_SECRET || "");

const server = http.createServer((req, res) => {
  if (req.method === "POST" && req.url === "/emit") {
    const headerSecret = String(req.headers["x-broadcast-secret"] || "");
    if (!BROADCAST_SECRET || headerSecret !== BROADCAST_SECRET) {
      res.writeHead(401, { "Content-Type": "application/json" });
      res.end(JSON.stringify({ success: false, message: "Unauthorized" }));
      return;
    }

    let body = "";
    req.on("data", (chunk) => {
      body += chunk;
    });

    req.on("end", () => {
      try {
        const parsed = JSON.parse(body || "{}");
        const event = String(parsed.event || "");
        const payload = parsed.payload ?? null;

        if (!event) {
          res.writeHead(400, { "Content-Type": "application/json" });
          res.end(JSON.stringify({ success: false, message: "Missing event" }));
          return;
        }

        io.emit(event, payload);
        res.writeHead(200, { "Content-Type": "application/json" });
        res.end(JSON.stringify({ success: true }));
      } catch (error) {
        res.writeHead(400, { "Content-Type": "application/json" });
        res.end(JSON.stringify({ success: false, message: error.message }));
      }
    });

    return;
  }

  res.writeHead(404, { "Content-Type": "application/json" });
  res.end(JSON.stringify({ success: false, message: "Not found" }));
});

const io = new Server(server, {
  path: "/socket.io",
  cors: {
    origin: "*",
    methods: ["GET", "POST"],
  },
});

io.on("connection", (socket) => {
  console.log("[socket.io] client connected", socket.id);

  socket.emit("server:ready", {
    at: new Date().toISOString(),
    id: socket.id,
  });

  socket.on("client:message", (payload) => {
    io.emit("server:message", {
      at: new Date().toISOString(),
      from: socket.id,
      payload,
    });
  });

  socket.on("disconnect", (reason) => {
    console.log("[socket.io] client disconnected", socket.id, reason);
  });
});

server.listen(PORT);
console.log(`[socket.io] listening on :${PORT} path=/socket.io`);
