'use strict';

/**
 * Simple test code for WebSocket
 */
const
    config = require('./config.json'),
    WebSocketClient = require('ws'),
    client = new WebSocketClient(config.wssHost + ':' + config.port + '/cabride', {
        perMessageDeflate: false
    });

client.on('connectFailed', function (error) {
    console.log(error);
    process.exit(1);
});

client.on('connect', function (connection) {
    connection.on('error', function (error) {
        console.log(error);
        process.exit(1);
    });
    connection.on('message', function (message) {
        if (message.type === 'utf8') {
            let payload = JSON.parse(message.utf8Data);
            if (payload.event === 'hello') {
                connection.close();
                process.exit(0);
            }
        }
        connection.close();
        process.exit(1);
    });
});
