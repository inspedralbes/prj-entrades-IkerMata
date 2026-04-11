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

// Reserva temporal: reenvia Bearer i, si OK, emet Socket.io a la sala (no depèn només de Redis).
app.post('/api/reservar', function (req, res) {
    var headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    };
    if (req.headers.authorization) {
        headers['Authorization'] = req.headers.authorization;
    }
    axios.post(LARAVEL_API_URL + '/reservar', req.body, { headers: headers })
        .then(function (response) {
            if ((response.status === 200 || response.status === 201) && response.data && response.data.ok) {
                var d = response.data;
                if (d.sessio_id != null && d.seient_id != null) {
                    var room = 'sessio:' + String(d.sessio_id);
                    if (d.reserva_activa) {
                        io.to(room).emit('seient-seleccionat', {
                            sessio_id: d.sessio_id,
                            seient_id: d.seient_id,
                            usuari_id: d.usuari_id
                        });
                    } else {
                        io.to(room).emit('seient-alliberat', {
                            sessio_id: d.sessio_id,
                            seient_id: d.seient_id
                        });
                    }
                }
            }
            res.status(response.status).json(response.data);
        })
        .catch(function (error) {
            console.error('Gateway: Error en reservar:', error.response ? error.response.status : error.message);
            res.status(error.response ? error.response.status : 500).json(error.response ? error.response.data : { error: 'Internal Server Error' });
        });
});

app.get('/api/peliculas', function (req, res) {
    axios.get(LARAVEL_API_URL + '/peliculas', { headers: { 'Accept': 'application/json' } })
        .then(function (response) {
            res.status(response.status).json(response.data);
        })
        .catch(function (error) {
            console.error('Gateway: Error en peliculas:', error.response ? error.response.status : error.message);
            res.status(error.response ? error.response.status : 500).json(error.response ? error.response.data : { error: 'Internal Server Error' });
        });
});

// Login: reenvia credencials a Laravel
app.post('/api/login', function (req, res) {
    axios.post(LARAVEL_API_URL + '/login', req.body, { headers: { 'Accept': 'application/json' } })
        .then(function (response) {
            res.status(response.status).json(response.data);
        })
        .catch(function (error) {
            console.error('Gateway: Error en login:', error.response ? error.response.status : error.message);
            res.status(error.response ? error.response.status : 500).json(error.response ? error.response.data : { error: 'Internal Server Error' });
        });
});

// Register: reenvia dades a Laravel
app.post('/api/register', function (req, res) {
    axios.post(LARAVEL_API_URL + '/register', req.body, { headers: { 'Accept': 'application/json' } })
        .then(function (response) {
            res.status(response.status).json(response.data);
        })
        .catch(function (error) {
            console.error('Gateway: Error en register:', error.response ? error.response.status : error.message);
            res.status(error.response ? error.response.status : 500).json(error.response ? error.response.data : { error: 'Internal Server Error' });
        });
});

// Logout: reenvia el token a Laravel per tancar sessió
app.post('/api/logout', function (req, res) {
    var headers = { 'Accept': 'application/json' };
    if (req.headers.authorization) {
        headers['Authorization'] = req.headers.authorization;
    }
    axios.post(LARAVEL_API_URL + '/logout', {}, { headers: headers })
        .then(function (response) {
            res.status(response.status).json(response.data);
        })
        .catch(function (error) {
            console.error('Gateway: Error en logout:', error.response ? error.response.status : error.message);
            res.status(error.response ? error.response.status : 500).json(error.response ? error.response.data : { error: 'Internal Server Error' });
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
            if ((response.status === 200 || response.status === 201) && response.data && response.data.ok) {
                var d = response.data;
                var sid = d.sessio_id;
                if (sid != null && d.entrades && d.entrades.length) {
                    var seientIds = d.entrades.map(function (e) {
                        return e.seient_id;
                    });
                    io.to('sessio:' + String(sid)).emit('compra-creada', {
                        sessio_id: sid,
                        seient_ids: seientIds
                    });
                }
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

// Laravel aplica REDIS_PREFIX als canals de PUBLISH (p. ex. "laravel-database-sessio").
// Escoltem el canal pla i el prefix per defecte d'APP_NAME=Laravel per cobrir tots dos casos.
var LARAVEL_DEFAULT_PUBSUB_PREFIX = process.env.LARAVEL_DEFAULT_PUBSUB_PREFIX || 'laravel-database-';

function emitToSessioRoom(sessioId, eventName, payload) {
    var room = 'sessio:' + String(sessioId);
    io.to(room).emit(eventName, payload);
}

function onRedisSessioChannel(event, data) {
    console.log('Redis event:', event, data);
    if (event === redisClient.EVENTS.COMPRA_CREADA) {
        emitToSessioRoom(data.sessio_id, 'compra-creada', data);
    } else if (event === redisClient.EVENTS.SEIENT_SELECCIONAT) {
        emitToSessioRoom(data.sessio_id, 'seient-seleccionat', data);
    } else if (event === redisClient.EVENTS.SEIENT_ALLIBERAT) {
        emitToSessioRoom(data.sessio_id, 'seient-alliberat', data);
    } else if (event === redisClient.EVENTS.AFORO_ACTUALITZAT) {
        emitToSessioRoom(data.sessio_id, 'aforo-actualitzat', data);
    }
}

redisClient.subscribe(redisClient.CHANNELS.SESSIO, onRedisSessioChannel);
redisClient.subscribe(LARAVEL_DEFAULT_PUBSUB_PREFIX + redisClient.CHANNELS.SESSIO, onRedisSessioChannel);

function onRedisPeliculaChannel(event, data) {
    console.log('Redis pelicula event:', event, data);
    var peliculaId = data.pelicula_id;
    io.to('pelicula:' + String(peliculaId)).emit('aforo-actualitzat', data);
}

redisClient.subscribe(redisClient.CHANNELS.PELICULA, onRedisPeliculaChannel);
redisClient.subscribe(LARAVEL_DEFAULT_PUBSUB_PREFIX + redisClient.CHANNELS.PELICULA, onRedisPeliculaChannel);

function onRedisCatalogChannel(event, data) {
    if (event === redisClient.EVENTS.CATALOG_ACTUALITZAT) {
        io.emit('catalog-actualitzat', data);
    }
}

redisClient.subscribe(redisClient.CHANNELS.CATALOG, onRedisCatalogChannel);
redisClient.subscribe(LARAVEL_DEFAULT_PUBSUB_PREFIX + redisClient.CHANNELS.CATALOG, onRedisCatalogChannel);

//==============================================================================
//================================ SOCKET.IO ================================
//==============================================================================

io.on('connection', function (socket) {
    console.log('a user connected', socket.id);

    socket.on('unirse-sessio', function (sessioId) {
        var room = 'sessio:' + String(sessioId);
        socket.join(room);
        console.log('socket', socket.id, 'joined', room);
    });

    socket.on('unirse-pelicula', function (peliculaId) {
        var room = 'pelicula:' + String(peliculaId);
        socket.join(room);
        console.log('socket', socket.id, 'joined', room);
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