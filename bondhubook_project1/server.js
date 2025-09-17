const express = require('express');
const http = require('http');
const { Server } = require('socket.io');

const app = express();
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: "*" } });

const users = {};  // Map user_id => socket.id

io.on('connection', (socket) => {
    console.log('User connected: ' + socket.id);

    // Client must send their user_id after connecting
    socket.on('register', (userId) => {
        users[userId] = socket.id;
        console.log(`Registered user ${userId} with socket ${socket.id}`);
    });

    socket.on('callUser', (data) => {
        const calleeSocket = users[data.userToCall];
        if (calleeSocket) {
            io.to(calleeSocket).emit('callUser', { signal: data.signalData, from: data.from });
        }
    });

    socket.on('answerCall', (data) => {
        const callerSocket = users[data.to];
        if (callerSocket) {
            io.to(callerSocket).emit('callAccepted', data.signal);
        }
    });

    socket.on('iceCandidate', (data) => {
        const otherSocket = users[data.to];
        if (otherSocket) {
            io.to(otherSocket).emit('iceCandidate', data.candidate);
        }
    });

    socket.on('endCall', (data) => {
        const otherSocket = users[data.to];
        if (otherSocket) {
            io.to(otherSocket).emit('callEnded');
        }
    });

    socket.on('disconnect', () => {
        console.log('User disconnected: ' + socket.id);
        // Remove user from users map
        for (const [userId, socketId] of Object.entries(users)) {
            if (socketId === socket.id) {
                delete users[userId];
                break;
            }
        }
    });
});

server.listen(3000, () => {
    console.log('Socket.IO signaling server running on port 3000');
});
