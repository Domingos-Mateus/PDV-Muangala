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
        'sales.discount' => 'Aplicar desconto nas vendas',
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

    'cash' => [
    'cash.operate' => 'Abrir e fechar o seu caixa, sangrias e suprimentos',
    'cash.manage'  => 'Gerir caixas e ver todos os turnos',
],

'reports' => [
    'reports.view' => 'Ver painel e todas as vendas da loja (lucro, funcionários)',
],

'role_templates' => [
    'Gerente'     => ['*'],
    'Funcionário' => ['products.view', 'stock.view', 'sales.create', 'sales.view', 'cash.operate'],
],

'billing' => [
    'customers.manage' => 'Gerir clientes',
    'services.manage'  => 'Gerir serviços e contratos',
    'billing.view'     => 'Ver cobranças, recibos e devedores',
    'billing.collect'  => 'Receber pagamentos de serviços no caixa',
    'billing.cancel' => 'Cancelar recibos de cobrança',
],

'role_templates' => [
    'Gerente'     => ['*'],
    'Funcionário' => ['products.view', 'stock.view', 'sales.create', 'sales.view', 'cash.operate', 'billing.collect'],
],

];
