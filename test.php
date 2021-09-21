<?php
$counts = [
    'orders' => 0,
    'order_statuses' => 0,
    'payment_methods' => 0,
    'delivery_methods' => 0,
    'NP_registries' => 0,
    'users' => 0,
    'groups_of_users' => 0,
    'clients' => 0,
    'groups_of_users' => 0,
    'clients' => 0,
    'groups_of_clients' => 0,
    'offices' => 0,
    'product_categories' => 0,
    'products' => 0,
    'manufacturers' => 0,
    'currencies' => 0,
    'sites' => 0,
    'attribute_categories' => 0,
    'attributes' => 0,
    'colors' => 0,
    'suppliers' => 0,
    'goods_arrival' => 0,
    'movement_of_goods' => 0,
    'write_off_of_goods' => 0
];
echo json_encode($counts);
