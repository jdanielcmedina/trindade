<?php
$title = $post['title'];
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <article class="max-w-4xl mx-auto">
        <?php if ($post['featured_image']): ?>
            <img src="<?= $post['featured_image'] ?>" alt="<?= $post['title'] ?>" class="w-full h-96 object-cover rounded-lg mb-8">
        <?php endif; ?>
        
        <h1 class="text-4xl font-bold mb-4"><?= $post['title'] ?></h1>
        
        <div class="flex items-center text-gray-600 mb-8">
            <span><?= date('d/m/Y', strtotime($post['published_at'])) ?></span>
            <?php if ($post['category_name']): ?>
                <span class="mx-2">•</span>
                <a href="/blog/categoria/<?= $post['category_slug'] ?>" class="hover:text-blue-600">
                    <?= $post['category_name'] ?>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="prose prose-lg max-w-none mb-8">
            <?= $post['content'] ?>
        </div>
        
        <?php if (!empty($post['tags'])): ?>
            <div class="mb-8">
                <h3 class="text-lg font-semibold mb-2">Tags:</h3>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($post['tags'] as $tag): ?>
                        <a href="/blog/tag/<?= $tag['slug'] ?>" class="bg-gray-100 px-3 py-1 rounded-full hover:bg-gray-200">
                            <?= $tag['name'] ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Seção de Comentários -->
        <section class="mt-12">
            <h2 class="text-2xl font-bold mb-6">Comentários</h2>
            
            <!-- Formulário de Comentário -->
            <form id="commentForm" class="mb-8">
                <div class="mb-4">
                    <label for="author_name" class="block text-gray-700 mb-2">Nome</label>
                    <input type="text" id="author_name" name="author_name" required
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="author_email" class="block text-gray-700 mb-2">Email</label>
                    <input type="email" id="author_email" name="author_email" required
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="content" class="block text-gray-700 mb-2">Comentário</label>
                    <textarea id="content" name="content" rows="4" required
                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    Enviar Comentário
                </button>
            </form>
            
            <!-- Lista de Comentários -->
            <div id="comments">
                <?php if (!empty($post['comments'])): ?>
                    <?php foreach ($post['comments'] as $comment): ?>
                        <div class="bg-gray-50 p-6 rounded-lg mb-4">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="font-semibold"><?= $comment['author_name'] ?></h4>
                                <span class="text-sm text-gray-600">
                                    <?= date('d/m/Y H:i', strtotime($comment['created_at'])) ?>
                                </span>
                            </div>
                            <p class="text-gray-700"><?= nl2br($comment['content']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-600">Nenhum comentário ainda. Seja o primeiro a comentar!</p>
                <?php endif; ?>
            </div>
        </section>
    </article>
</div>

<script>
document.getElementById('commentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const response = await fetch('/blog/comentar/<?= $post['id'] ?>', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
        alert('Comentário enviado com sucesso! Aguardando aprovação.');
        this.reset();
    } else {
        alert('Erro ao enviar comentário. Tente novamente.');
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?> 