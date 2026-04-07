//==============================================================================
//================================ IMPORTS =====================================
//==============================================================================

var express = require('express');
var http = require('http');
var Server = require("socket.io").Server;
var axios = require('axios');
var cors = require('cors');
var redisClient = require('./redisClient');

//==============================================================================
//================================ VARIABLES ===================================
//==============================================================================

var app = express();
app.use(cors());
app.use(express.json());

var server = http.createServer(app);
var io = new Server(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
});

var LARAVEL_API_URL = process.env.LARAVEL_API_URL || 'http://web/api';

//==============================================================================
//================================ RUTES ======================================
//==============================================================================

// Ruta arrel
app.get('/', function (req, res) {
    res.send('Gateway is running');
});

// Redis health check (infra)
app.get('/health/redis', function (req, res) {
    redisClient.pingRedis(function (err, pong) {
        if (err) {
            return res.status(500).json({ ok: false, error: String(err.message || err) });
        }
        res.json({ ok: true, redis: pong });
    });
});

// Proxy route example
app.post('/api/reservar', function (req, res) {
    axios.post(LARAVEL_API_URL + '/reservar', req.body)
        .then(function (response) {
            if (response.status === 200) {
                io.emit('reserva-confirmada', response.data);
            }
            res.status(response.status).json(response.data);
        })
        .catch(function (error) {
            res.status(error.response.status || 500).json(error.response.data || { error: 'Internal Server Error' });
        });
});

app.get('/api/peliculas', function (req, res) {
    axios.get(LARAVEL_API_URL + '/pelis')
        .then(function (response) {
            res.status(response.status).json(response.data);
        })
        .catch(function (error) {
            res.status(error.response.status || 500).json(error.response.data || { error: 'Internal Server Error' });
        });
});

// Login: reenvia credencials a Laravel
app.post('/api/login', function (req, res) {
    axios.post(LARAVEL_API_URL + '/login', req.body)
        .then(function (response) {
            res.status(response.status).json(response.data);
        })
        .catch(function (error) {
            res.status(error.response.status || 500).json(error.response.data || { error: 'Internal Server Error' });
        });
});

// Register: reenvia dades a Laravel
app.post('/api/register', function (req, res) {
    axios.post(LARAVEL_API_URL + '/register', req.body)
        .then(function (response) {
            res.status(response.status).json(response.data);
        })
        .catch(function (error) {
            res.status(error.response.status || 500).json(error.response.data || { error: 'Internal Server Error' });
        });
});

// Logout: reenvia el token a Laravel per tancar sessió
app.post('/api/logout', function (req, res) {
    var headers = {};
    if (req.headers.authorization) {
        headers['Authorization'] = req.headers.authorization;
    }
    axios.post(LARAVEL_API_URL + '/logout', {}, { headers: headers })
        .then(function (response) {
            res.status(response.status).json(response.data);
        })
        .catch(function (error) {
            var status = 500;
            var payload = { error: 'Internal Server Error' };
            if (error.response) {
                status = error.response.status;
                payload = error.response.data;
            }
            res.status(status).json(payload);
        });
});

// Compra d'entrades: reenvia el cos i el Bearer cap a Laravel
app.post('/api/comprar', function (req, res) {
    var headers = { 'Content-Type': 'application/json' };
    if (req.headers.authorization) {
        headers['Authorization'] = req.headers.authorization;
    }
    axios.post(LARAVEL_API_URL + '/comprar', req.body, { headers: headers })
        .then(function (response) {
            if (response.status === 200 || response.status === 201) {
                io.emit('compra-registrada', response.data);
            }
            res.status(response.status).json(response.data);
        })
        .catch(function (error) {
            var status = 500;
            var payload = { error: 'Internal Server Error' };
            if (error.response) {
                status = error.response.status;
                payload = error.response.data;
            }
            res.status(status).json(payload);
        });
});

//==============================================================================
//================================ SUBSCRIPCIONS REDIS ==========================
//==============================================================================

// Subscripció a canals Redis i reemissió per Socket.io
redisClient.subscribe(redisClient.CHANNELS.SESSIO, function (event, data) {
    console.log('Redis event:', event, data);
    if (event === redisClient.EVENTS.COMPRA_CREADA) {
        var sessioId = data.sessio_id;
        io.to('sessio:' + sessioId).emit('compra-creada', data);
    } else if (event === redisClient.EVENTS.SEIENT_SELECCIONAT) {
        var sessioId = data.sessio_id;
        io.to('sessio:' + sessioId).emit('seient-seleccionat', data);
    } else if (event === redisClient.EVENTS.SEIENT_ALLIBERAT) {
        var sessioId = data.sessio_id;
        io.to('sessio:' + sessioId).emit('seient-alliberat', data);
    }
});

redisClient.subscribe(redisClient.CHANNELS.PELICULA, function (event, data) {
    console.log('Redis pelicula event:', event, data);
    var peliculaId = data.pelicula_id;
    io.to('pelicula:' + peliculaId).emit('aforo-actualitzat', data);
});

//==============================================================================
//================================ SOCKET.IO ================================
//==============================================================================

io.on('connection', function (socket) {
    console.log('a user connected', socket.id);

    socket.on('unirse-sessio', function (sessioId) {
        socket.join('sessio:' + sessioId);
        console.log('socket', socket.id, 'joined sessio:', sessioId);
    });

    socket.on('unirse-pelicula', function (peliculaId) {
        socket.join('pelicula:' + peliculaId);
        console.log('socket', socket.id, 'joined pelicula:', peliculaId);
    });

    socket.on('disconnect', function () {
        console.log('user disconnected', socket.id);
    });
});

//==============================================================================
//================================ SERVER =====================================
//==============================================================================

var PORT = process.env.PORT || 3001;
server.listen(PORT, function () {
    console.log('Gateway listening on port ' + PORT);
});