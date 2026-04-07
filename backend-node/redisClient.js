//==============================================================================
//================================ IMPORTS =====================================
//==============================================================================

var Redis = require('ioredis');

//==============================================================================
//================================ VARIABLES ===================================
//==============================================================================

var client = null;
var subscriber = null;

//==============================================================================
//================================ CONSTANTS ==================================
//==============================================================================

var CHANNELS = {
    SESSIO: 'sessio',
    PELICULA: 'pelicula',
    GLOBAL: 'temps-real'
};

var EVENTS = {
    COMPRA_CREADA: 'compra:creada',
    SEIENT_SELECCIONAT: 'seient:seleccionat',
    SEIENT_ALLIBERAT: 'seient:alliberat',
    AFORO_ACTUALITZAT: 'aforo:actualitzat'
};

//==============================================================================
//================================ FUNCIONS ====================================
//==============================================================================

function getRedis() {
    if (client) {
        return client;
    }
    var host = process.env.REDIS_HOST || '127.0.0.1';
    var port = parseInt(process.env.REDIS_PORT || '6379', 10);
    client = new Redis({
        host: host,
        port: port,
        maxRetriesPerRequest: 2
    });
    return client;
}

function getSubscriber() {
    if (subscriber) {
        return subscriber;
    }
    var host = process.env.REDIS_HOST || '127.0.0.1';
    var port = parseInt(process.env.REDIS_PORT || '6379', 10);
    subscriber = new Redis({
        host: host,
        port: port,
        maxRetriesPerRequest: 2
    });
    return subscriber;
}

function pingRedis(callback) {
    getRedis()
        .ping()
        .then(function (res) {
            callback(null, res);
        })
        .catch(function (err) {
            callback(err, null);
        });
}

function publish(channel, event, data) {
    var redis = getRedis();
    var message = JSON.stringify({
        event: event,
        data: data,
        timestamp: Date.now()
    });
    return redis.publish(channel, message);
}

function subscribe(channel, callback) {
    var sub = getSubscriber();
    sub.subscribe(channel);
    sub.on('message', function (ch, message) {
        if (ch === channel) {
            try {
                var parsed = JSON.parse(message);
                callback(parsed.event, parsed.data);
            } catch (e) {
                console.error('Redis message parse error:', e);
            }
        }
    });
}

module.exports = {
    getRedis: getRedis,
    getSubscriber: getSubscriber,
    pingRedis: pingRedis,
    publish: publish,
    subscribe: subscribe,
    CHANNELS: CHANNELS,
    EVENTS: EVENTS
};
