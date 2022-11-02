const express = require('express');
const http = require('http');
const { Server } = require("socket.io");

const app = express();
const server = http.createServer(app);
const io = new Server(server);

app.get('/', (req, res) => {
    res.sendFile(__dirname + '/View/index.html');
});

server.listen(3000, () => {
    console.log('listening on port : 3000');
});

var clients = new Map();

var products = new Map();
products.set("voiture", new Map());
products.set("maison", new Map());
products.set("livre", new Map());

var prices = new Map();
prices.set("voiture", 0);
prices.set("maison", 0);
prices.set("livre", 0);

io.on('connection', (socket) => {
    console.log('a user connected '+socket.id);
    clients.set(socket.id, socket);
    socket.on('disconnect', () => {
        console.log('user disconnected '+socket.id);
        clients.delete(socket.id);
        products.forEach(product => {
            product.delete(socket.id);
        });
    });

    socket.on("add", (product) => {
        if (products.get(product) !== undefined)
        {
            products.get(product).set(socket.id, socket);
            socket.emit("offer", product, prices.get(product));
        }
    });

    socket.on("offer", (product, price) => {
        if (price > prices.get(product)) {
            prices.set(product, price);
            products.get(product).forEach(client => {
                client.emit("offer", product, price);
            })
        }
    });
});


