<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="w-64 bg-indigo-700">
            <div class="flex items-center justify-center h-16 px-4 bg-indigo-800">
                <h1 class="text-xl font-semibold text-white">
                    <?= $app->admin->config['title'] ?>
                </h1>
            </div>
            
            <nav class="mt-8">
                <div class="px-4 space-y-1">
                    <?php foreach ($app->admin->config['menu'] as $route => $item): ?>
                        <a href="<?= $app->admin->config['prefix'] ?>/<?= $route ?>" 
                           class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-white hover:bg-indigo-600 
                                  <?= strpos($_SERVER['REQUEST_URI'], $route) !== false ? 'bg-indigo-800' : '' ?>">
                            <svg class="mr-3 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <?php if ($item['icon'] === 'home'): ?>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                <?php elseif ($item['icon'] === 'users'): ?>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                <?php elseif ($item['icon'] === 'settings'): ?>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <?php endif; ?>
                            </svg>
                            <?= $item['title'] ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Top Bar -->
            <div class="h-16 bg-white shadow px-4 flex items-center justify-between">
                <div></div>
                <div class="flex items-center">
                    <?php if ($user = $app->session->get('admin_user')): ?>
                        <span class="text-gray-700 mr-4"><?= $user['name'] ?></span>
                        <a href="<?= $app->admin->config['prefix'] ?>/logout" 
                           class="text-sm text-gray-500 hover:text-gray-700">
                            Sair
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 