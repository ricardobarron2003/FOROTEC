<div class="sidebar">

    <form action="busqueda.php" method="GET" class="mb-4">
    <div class="input-group">
        <input type="text" name="termino" class="form-control" placeholder="Buscar..." 
               value="<?php echo isset($_GET['termino']) ? htmlspecialchars($_GET['termino']) : ''; ?>">
        <button class="btn btn-outline-primary" type="submit">Buscar</button>
    </div>
</form>

    <a href="chats_usuario.php" class="d-block mt-3">Chats</a>
    <a href="Seguidores.php" class="d-block">Seguidores</a>
    <a href="mis_publicaciones.php" class="d-block">Mis publicaciones</a>
</div>
