//==============================================================================
//================================ IMPORTS =====================================
//==============================================================================

var Redis = require('ioredis');

//==============================================================================
//================================ VARIABLES ===================================
//==============================================================================

var client = null;

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

module.exports = {
    getRedis: getRedis,
    pingRedis: pingRedis
};
