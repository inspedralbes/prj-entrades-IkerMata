const express = require('express');
const http = require('http');
const { Server } = require("socket.io");
const axios = require('axios');
const cors = require('cors');

const app = express();
app.use(cors());
app.use(express.json());

const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
});

const LARAVEL_API_URL = process.env.LARAVEL_API_URL || 'http://web/api';

app.get('/', (req, res) => {
    res.send('Gateway is running');
});

// Proxy route example
app.post('/api/reservar', async (req, res) => {
    try {
        const response = await axios.post(`${LARAVEL_API_URL}/reservar`, req.body);
        // Broadcast event if success
        if (response.status === 200) {
            io.emit('reserva-confirmada', response.data);
        }
        res.status(response.status).json(response.data);
    } catch (error) {
        res.status(error.response?.status || 500).json(error.response?.data || { error: 'Internal Server Error' });
    }
});

io.on('connection', (socket) => {
    console.log('a user connected');
    socket.on('disconnect', () => {
        console.log('user disconnected');
    });
});

const PORT = process.env.PORT || 3001;
server.listen(PORT, () => {
    console.log(`Gateway listening on port ${PORT}`);
});
