<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $this->escape($title ?? 'Default Title') ?></title>
</head>
<body>
    <h1><?= $this->escape($heading ?? 'Welcome') ?></h1>

    <?php if (isset($message)): ?>
        <p><?= $this->escape($message) ?></p>
    <?php endif; ?>

    <?php if (isset($items) && count($items) > 0): ?>
        <ul>
            <?php foreach ($items as $item): ?>
                <li><?= $this->escape($item) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No items to display.</p>
    <?php endif; ?>

    <footer>
        <p>&copy; <?= date('Y') ?> My Application</p>
    </footer>
</body>
</html>
