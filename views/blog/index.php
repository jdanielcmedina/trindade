<?php
$title = 'Blog';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold mb-8">Blog</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($posts as $post): ?>
            <article class="bg-white rounded-lg shadow-md overflow-hidden">
                <?php if ($post['featured_image']): ?>
                    <img src="<?= $post['featured_image'] ?>" alt="<?= $post['title'] ?>" class="w-full h-48 object-cover">
                <?php endif; ?>
                
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-2">
                        <a href="/blog/<?= $post['slug'] ?>" class="hover:text-blue-600">
                            <?= $post['title'] ?>
                        </a>
                    </h2>
                    
                    <div class="text-sm text-gray-600 mb-4">
                        <span><?= date('d/m/Y', strtotime($post['published_at'])) ?></span>
                        <?php if ($post['category_name']): ?>
                            • <a href="/blog/categoria/<?= $post['category_slug'] ?>" class="hover:text-blue-600">
                                <?= $post['category_name'] ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($post['excerpt']): ?>
                        <p class="text-gray-700 mb-4"><?= $post['excerpt'] ?></p>
                    <?php endif; ?>
                    
                    <a href="/blog/<?= $post['slug'] ?>" class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Ler mais
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?> 