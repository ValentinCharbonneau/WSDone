<?php

namespace App;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Socket implements MessageComponentInterface
{
    private $products;
    private $prices;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->products = ["voiture" => [], "maison" => [], "livre" => []];
        $this->prices = ["voiture" => 0, "maison" => 0, "livre" => 0];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        
        echo "Connection ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = \json_decode($msg);
        if (isset($data->type) && $data->type == 'add')
        {
            $this->products[$data->product][$from->resourceId] = $from;
            $from->send(\json_encode(['type' => 'offer', 'product' => $data->product, 'price' => $this->prices[$data->product]]));
        }
        elseif (isset($data->type) && $data->type == 'offer')
        {
            if ($this->prices[$data->product] < $data->price)
            {
                $this->prices[$data->product] = $data->price;
            }
            foreach ($this->products[$data->product] as $client)
            {
                $client->send(\json_encode(['type' => 'offer', 'product' => $data->product, 'price' => $this->prices[$data->product]]));
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        foreach ($this->products as $product)
        {
            if (isset($product[$conn->resourceId]))
            {
                unset($product[$conn->resourceId]);
            }
        }

        echo "Disconnection ({$conn->resourceId})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
    }
}


