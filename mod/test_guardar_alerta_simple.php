<?php
// mod/test_guardar_alerta_simple.php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Guardar Alerta</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        input, select, textarea { width: 100%; padding: 8px; margin: 5px 0 15px; }
        button { padding: 10px 20px; background: #39a900; color: white; border: none; cursor: pointer; }
        pre { background: #f4f4f4; padding: 10px; }
    </style>
</head>
<body>
    <h2>🔍 Test Guardar Alerta - Modo Depuración</h2>
    
    <form id="testForm">
        <input type="hidden" name="estudiante_id" id="estudiante_id" value="121">
        <input type="hidden" name="instructor_id" value="1">
        
        <label>Tipo:</label>
        <select name="tipo" required>
            <option value="Académica">Académica</option>
            <option value="Disciplinaria">Disciplinaria</option>
            <option value="Actitudinal">Actitudinal</option>
        </select>
        
        <label>Nivel Riesgo:</label>
        <select name="nivel_riesgo" required>
            <option value="Bajo">Bajo</option>
            <option value="Medio">Medio</option>
            <option value="Alto">Alto</option>
        </select>
        
        <label>Descripción:</label>
        <textarea name="descripcion" required>Alerta de prueba desde test</textarea>
        
        <button type="submit">Probar Guardar</button>
    </form>
    
    <h3>Respuesta del servidor:</h3>
    <pre id="resultado">Esperando...</pre>

    <script>
    document.getElementById('testForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        document.getElementById('resultado').textContent = 'Enviando...';
        
        fetch('guardar_alerta.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            return response.text().then(text => {
                return { text, ok: response.ok, status: response.status };
            });
        })
        .then(({ text, ok, status }) => {
            document.getElementById('resultado').innerHTML = 
                `Status: ${status}\n\n` +
                `Respuesta raw:\n${text}\n\n` +
                `¿Es JSON? ${esJSON(text) ? '✅ Sí' : '❌ No'}`;
            
            if (esJSON(text)) {
                let json = JSON.parse(text);
                document.getElementById('resultado').innerHTML += 
                    `\n\n✅ JSON parseado:\n${JSON.stringify(json, null, 2)}`;
            }
        })
        .catch(error => {
            document.getElementById('resultado').innerHTML = '❌ Error: ' + error;
        });
    });
    
    function esJSON(text) {
        try {
            JSON.parse(text);
            return true;
        } catch {
            return false;
        }
    }
    </script>
</body>
</html>