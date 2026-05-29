const { Server } = require("socket.io");

const PORT = Number(process.env.SOCKET_IO_PORT || 3001);

const io = new Server({
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

io.listen(PORT);
console.log(`[socket.io] listening on :${PORT} path=/socket.io`);
