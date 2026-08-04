<!DOCTYPE html>
<html lang="ca">
<head><meta charset="UTF-8"></head>
<body style="font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 24px;">
    <h2 style="color: #1A5C38;">Nou suggeriment rebut</h2>

    <p><strong>Colla:</strong> {{ $suggeriment->colla->name }}</p>
    <p><strong>Casteller:</strong> {{ $suggeriment->casteller->alias }}
        ({{ $suggeriment->casteller->email }})</p>
    <p><strong>Data:</strong> {{ $suggeriment->created_at->format('d/m/Y H:i') }}</p>

    <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">

    <p style="white-space: pre-wrap; background: #f5f5f5; padding: 16px; border-radius: 6px;">{{ $suggeriment->text }}</p>
</body>
</html>
