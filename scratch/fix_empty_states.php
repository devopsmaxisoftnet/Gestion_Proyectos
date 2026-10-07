<?php require __DIR__ . '/../config/database.php'; dbExecute("UPDATE oportunidades SET estado = 'identificada' WHERE estado = '' OR estado IS NULL");
