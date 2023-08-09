<?php
session_start();
$inApp = false;
$messageTop = "Welcome to PHP Terminal - ". date("m.d.y h:i:s")."\n";
$db = new PDO("sqlite:.db");

/**Database Handling**/
//CREATE TABLE IF NOT EXISTS users('id' INTEGER Primary Key AUTOINCREMENT, 'username' TEXT NOT NULL UNIQUE, 'Password' TEXT NOTNUL)
try{
	$db->exec("CREATE TABLE IF NOT EXISTS users('id' INTEGER Primary Key AUTOINCREMENT, 'username' TEXT NOT NULL UNIQUE, 'password' TEXT NOT NULL)");

	$db->exec("INSERT INTO users(username, password) VALUES ('terminator','". '$2y$10$cejXlzXWVa0lfeZdEvEm9u08KiaVsTWbi45NBSd0GaDTOOkdpnjOG'. "')");
}catch(Exception $e){
	//echo $e->getMessage();
}

function createUser($user, $pass){
	global $db;
	
	try{
		$stm = $db->prepare("INSERT INTO users(username, password) values (?, ?)");
		return $stm->execute([$user, password_hash($pass, PASSWORD_DEFAULT)]);
	}catch(Exception $e){
	}
}

function updateUser($user, $pass){
	global $db;
	
	try{
		$stm = $db->prepare("UPDATE users SET password=? WHERE username = ?");
		return $stm->execute([password_hash($pass, PASSWORD_DEFAULT),$user]);
	}catch(Exception $e){
        return -1;
	}
}
function getAll(){
	global $db;
	
	try{
		$stm = $db->prepare("SELECT * FROM users");
		$isReg = $stm->execute();
		$data = $stm->fetchAll();
		return print_r($data, true);
	}catch(Exception $e){return "no data";}
}

function login($user, $pass){
	global $db;
	
	try{
		$stm = $db->prepare("SELECT password FROM users WHERE username = ?");
		$isReg = $stm->execute([$user]);
		$hash = $stm->fetchAll()[0]['password'];
		if(password_verify($pass, $hash)){
			return true;
		}
		return false;
	}catch(Exception $e){return false;}
}/**/
/**End of Database Handling**/

function stratWith($wholeText, $startingWord){
    $op = explode(" ", $wholeText);
    if(($op[0]===$startingWord))
    {
        return true;
    }
    else{
        return false;
    }

}

/**Begining of normal App Logic**/
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $requestData = json_decode(file_get_contents("php://input"), true);
    $command = $requestData["command"];
    $inApp = true;

    
    
    if(stratWith($command,"login")){
        $userInfo = explode(" ",$command);
        //strstr($command,"user:") && strstr($command,"pass:")
                    
        if(count($userInfo)>2){
            $uname = explode(":", $userInfo[1]);
            $upasswd = explode(":", $userInfo[2]);
            if(count($uname)> 1 && count($upasswd) > 1)
                if(login($uname[1], $upasswd[1])){
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

    if(isset($_SESSION['user'])){

        if(stratWith($command,"create")){
            $userInfo = explode(" ",$command);
            if(count($userInfo)>2){
                $uname = explode(":", $userInfo[1]);
                $upasswd = explode(":", $userInfo[2]);
                if(count($uname)> 1 && count($upasswd) > 1){
                    $i = createUser($uname[1], $upasswd[1]);
                    echo "succesful created new account";
                    return;
                }else{
                    echo "Failed to create new account";
                    return;
                }
                
            }
        }
        else if(stratWith($command,"update")){
            $userInfo = explode(" ",$command);
            if(count($userInfo)>2){
                $uname = explode(":", $userInfo[1]);
                $upasswd = explode(":", $userInfo[2]);
                if(count($uname)> 1 && count($upasswd) > 1){
                    $i = updateUser($uname[1], $upasswd[1]);
                    echo "succesful updated password";
                    return;
                }else{
                    echo "Failed to update the password";
                    return;
                }
                
            }
        }

        exec($command . "  2>&1", $output,$ret);
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
        <title>PHP Terminal</title>
        
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
            <pre id="output"><?php echo $messageTop;?></pre>
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
            var userCredentials = "";
            var userDetails = "";


        terminalInput.addEventListener("keyup", function (event) {
            if (event.key === "Enter") {
                
                var dIn = terminalInput.value;
                if(dIn == "login" || dIn == "create" || dIn == "update"){
                    userDetails = dIn + " user:";
                    terminalOutput.textContent += "$ user: ";
                    terminalInput.value = "";
                    return;
                }
                else if(userDetails == "login user:" || userDetails == "create user:" || userDetails == "update user:"){
                    userDetails += dIn + " pass:";
                    terminalOutput.textContent += dIn + "\n$ pass: \n";
                    terminalInput.type = "password";
                    terminalInput.value = "";
                    return;
                }
                else if (userDetails.endsWith("pass:")){
                     userDetails += dIn;
                    terminalInput.value = "";
                    terminalInput.type = "text";
                }

                const command = ((userDetails.includes("user")) ? userDetails : terminalInput.value);
                userDetails = "";
                if (!(command.includes("pass:") && command.includes("user:"))) {
                    terminalOutput.textContent += "$ " + command + "\n";
                    commandList.push(command); 
                }else{
                    terminalInput.type = "text";
                }
                commandIndex = commandList.length;

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

                    if(output.includes("Session terminated by user")){
                        window.open('','_parent','_self');
                        window.close();

                    }
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
            
            // if (terminalInput.value.endsWith("login")) {
            //     userDetails += "login ";
            //     terminalOutput.textContext += "$user: \n";
            //     terminalInput.value = "";
            // }



            // if (terminalInput.value.endsWith("pass:")) {
            //     userCredentials = terminalInput.value;
            //     terminalInput.value = "";
            //     terminalInput.type = "password";
            // }
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