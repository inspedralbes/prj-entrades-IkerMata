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

app.get('/api/peliculas', async (req, res) => {
    try {
        const response = await axios.get(`${LARAVEL_API_URL}/pelis`);
        res.status(response.status).json(response.data);
    } catch (error) {
        res.status(error.response?.status || 500).json(error.response?.data || { error: 'Internal Server Error' });
    }
});

// Compra d'entrades: reenvia el cos i el Bearer cap a Laravel
app.post('/api/comprar', async (req, res) => {
    try {
        var cap = { 'Content-Type': 'application/json' };
        if (req.headers.authorization) {
            cap['Authorization'] = req.headers.authorization;
        }
        const response = await axios.post(`${LARAVEL_API_URL}/comprar`, req.body, {
            headers: cap
        });
        if (response.status === 200 || response.status === 201) {
            io.emit('compra-registrada', response.data);
        }
        res.status(response.status).json(response.data);
    } catch (error) {
        var status = 500;
        var payload = { error: 'Internal Server Error' };
        if (error.response) {
            status = error.response.status;
            payload = error.response.data;
        }
        res.status(status).json(payload);
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
