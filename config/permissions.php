<?php

return [
    'catalog' => [
        'products.view'     => 'Ver produtos',
        'products.create'   => 'Cadastrar produtos',
        'products.update'   => 'Editar produtos',
        'products.delete'   => 'Apagar produtos',
        'categories.manage' => 'Gerir categorias',
    ],
    'stock' => [
        'stock.view'   => 'Ver estoque',
        'stock.adjust' => 'Ajustar estoque',
        'stock.price'  => 'Alterar preço de venda da loja',
    ],
    'sales' => [
        'sales.create' => 'Registar vendas',
        'sales.view'   => 'Ver vendas',
        'sales.cancel' => 'Cancelar vendas',
    ],
    'purchases' => [
        'suppliers.manage'  => 'Gerir fornecedores',
        'purchases.view'    => 'Ver compras',
        'purchases.create'  => 'Registar compras',
        'purchases.receive' => 'Receber compras (dá entrada no estoque)',
    ],
    'users' => [
        'users.manage' => 'Gerir utilizadores e perfis da loja',
    ],

    'role_templates' => [
        'Gerente'     => ['*'],
        'Funcionário' => ['products.view', 'stock.view', 'sales.create', 'sales.view'],
    ],
];
