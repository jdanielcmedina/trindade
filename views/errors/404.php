<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $code ?> — <?= htmlspecialchars($message) ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #09090b; color: #fafafa;
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; margin: 0;
        }
        .box { text-align: center; max-width: 400px; }
        .code { font-size: 5rem; font-weight: 700; color: #3b82f6; line-height: 1; }
        h1 { font-size: 1.25rem; font-weight: 600; margin: 1rem 0 .5rem; }
        p { color: #a1a1aa; font-size: .9rem; margin-bottom: 2rem; }
        a { color: #3b82f6; text-decoration: none; font-size: .85rem; }
    </style>
</head>
<body>
    <div class="box">
        <div class="code"><?= $code ?></div>
        <h1><?= htmlspecialchars($message) ?></h1>
        <p>The page you are looking for does not exist or has been moved.</p>
        <a href="/">&larr; Back to home</a>
    </div>
</body>
</html>
