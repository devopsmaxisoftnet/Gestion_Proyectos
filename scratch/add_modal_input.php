<?php
$c = file_get_contents('c:/xampp/htdocs/Gestion_Proyectos/views/oportunidades/ver_oportunidad.php');
$c = str_replace('<input type="hidden" name="estado_actual"', '<?php if(isset($_GET[\'modal_iframe\'])): ?><input type="hidden" name="modal_iframe" value="1"><?php endif; ?> <input type="hidden" name="estado_actual"', $c);
file_put_contents('c:/xampp/htdocs/Gestion_Proyectos/views/oportunidades/ver_oportunidad.php', $c);
