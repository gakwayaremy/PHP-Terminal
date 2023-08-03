<?php
session_start();
$inApp = false;
$messageTop = "Welcome to PHP Terminal - ". date("m.d.y h:i:s")."\n";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $requestData = json_decode(file_get_contents("php://input"), true);
    $command = $requestData["command"];
    $inApp = true;

    if(strstr($command,"user:") && strstr($command,"pass:")){
        $userInfo = explode(",",$command);
        
        if(count($userInfo)>1){
            $uname = explode(":", $userInfo[0]);
            $upasswd = explode(":", $userInfo[1]);
            if(count($uname)> 1 && count($upasswd) > 1)
                if($uname[1] == "terminator" && $upasswd[1] == "lolipop"){
                    $_SESSION[$uname[0]] = $uname[1];
                    $messageTop = "Welcome to PHP Terminal - ". date("m.d.y h:i:s")."\n";   
                    echo "succesful logedin";
                    return;
                }
        }
    }
    else if($command == "exit"){
        unset($_SESSION['user']);
        echo "Session terminated by user";
        return;
    }

    if(isset($_SESSION['user']) && $_SESSION['user'] == "terminator"){
        exec($command, $output,$ret);
        echo implode("\n", $output);
    }else{
        echo "Access denied. Contact your administrator";
    }
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
                height: 100vh;
                background-color: black;
                color: white;
                font-family: monospace;
                font-size: 24px;
                padding: 10px;
                overflow-y: auto;
            }

            #input {
                width: 100%;
                background-color: transparent;
                border: none;
                color: white;
                font-family: monospace;
                font-size: 24px;
                outline: none;
            }
        </style>
    </head>
    <body>
        <div id="terminal" class="terminal">
            <pre id="output"><?php echo $messageTop;?> </pre>
            <input type="text" id="input" autofocus>
        </div>
        <script>
            let commandList = [];
            var commandIndex =commandList.length;
            let messageTop = <?php echo json_encode($messageTop);?>;

            let fontSize1 = 24;
            let fontSize2 = 24;
            const terminalParent = document.getElementById("terminal");
            const terminalOutput = document.getElementById("output");
            const terminalInput = document.getElementById("input");


        terminalInput.addEventListener("keyup", function (event) {
            if (event.key === "Enter") {
                const command = terminalInput.value;
                if (!(command.includes("pass:") && command.includes("user:"))) {
                    terminalOutput.textContent += "$ " + command + "\n";
                    commandList.push(command); 
                }else{
                    terminalInput.type = "text";
                }
                commandIndex =commandList.length;

                if(command === "clear" || command === "cls"){
                    terminalOutput.textContent = messageTop;
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

        function updateSize(){

            terminalParent.style.width =window.innerWidth;
            terminalParent.style.height =window.innerHeight;
        }

        updateSize();
        window.addEventListener("resize", updateSize);


        terminalParent.addEventListener("keydown", function(event){
            if (terminalInput.value.includes("pass:")) {
                terminalInput.type = "password";
            }
            if(event.ctrlKey && event.key === '='){
                ++fontSize1; ++fontSize2;
                terminalParent.style.fontSize = fontSize1 + "px";
                terminalInput.style.fontSize = fontSize2 + "px";
                
            }
            if(event.ctrlKey && event.key === '-'){
                terminalParent.style.fontSize = --fontSize1 + "px";
                terminalInput.style.fontSize = --fontSize2 + "px";
            }

            if(event.key === "ArrowUp"){  
                terminalInput.value = (commandIndex - 1 < commandList.length && commandIndex - 1 >= 0) ? commandList[--commandIndex] : "";
            }
            if(event.key === "ArrowDown"){
                terminalInput.value = (commandIndex + 1 < commandList.length && commandIndex + 1 >= 0) ? commandList[ ++commandIndex] : "";
            }
        });
    </script>

    </body>
    </html>
<?php }?>