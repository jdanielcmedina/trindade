<?php
// Header
include __DIR__ . '/../../../views/partials/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestão de Utilizadores</h1>
        <a href="/admin/users/new" class="btn btn-primary">Novo Utilizador</a>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?= $user['id'] ?></td>
                <td><?= $user['name'] ?></td>
                <td><?= $user['email'] ?></td>
                <td>
                    <a href="/admin/users/edit/<?= $user['id'] ?>" class="btn btn-sm btn-info">Editar</a>
                    <button 
                        onclick="deleteUser(<?= $user['id'] ?>)" 
                        class="btn btn-sm btn-danger">
                        Eliminar
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
async function deleteUser(id) {
    if (!confirm('Tens a certeza que queres eliminar este utilizador?')) return;
    
    try {
        const response = await fetch(`/api/users/${id}`, {
            method: 'DELETE'
        });
        
        if (response.ok) {
            window.location.reload();
        } else {
            alert('Erro ao eliminar utilizador');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Erro ao eliminar utilizador');
    }
}
</script>

<?php
// Footer
include __DIR__ . '/../../../views/partials/footer.php';
?> 