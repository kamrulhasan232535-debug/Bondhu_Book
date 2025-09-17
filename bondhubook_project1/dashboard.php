<?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);
require 'db.php';
if (!$conn) {
    die("Database connection failed");
}
if (!isset($_SESSION['user_id'])) {
    if (isset($_COOKIE['user_id'])) {
        $cookie_user_id = (int)$_COOKIE['user_id'];
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->bind_param("i", $cookie_user_id);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $_SESSION['user_id'] = $cookie_user_id;
        }
        $stmt->close();
    }
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.html");
        exit;
    }
}
$user_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_name, $profile_picture);
$stmt->fetch();
$stmt->close();
$profile_image_url = $profile_picture ? htmlspecialchars($profile_picture) : 'icon/user.png';
$data = [];
$media_url = $caption = "";
$type = "unknown";
$id = 0;
$stroyimg = $stroyvideo = $stroyaudio = $stroytext = "";
$stmnt1 = $conn->prepare("SELECT post_content FROM posts WHERE user_id=?");
$stmnt1->bind_param("i", $user_id);
$stmnt1->execute();
$result = $stmnt1->get_result();

while ($row = $result->fetch_assoc()) {
    $data[] = $row['post_content'];
}
$data = array_reverse($data);
$stmnt1->close();

// Get user media
$stmnt2 = $conn->prepare("SELECT media_url, caption, id FROM user_media WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmnt2->bind_param("i", $user_id);
$stmnt2->execute();
$result1 = $stmnt2->get_result();

if ($row = $result1->fetch_assoc()) {
    $media_url = htmlspecialchars($row["media_url"]);
    $caption = htmlspecialchars($row["caption"]);
    $id = (int)$row["id"];
    
    $extension = strtolower(pathinfo($media_url, PATHINFO_EXTENSION));
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $type = "image";
    } elseif (in_array($extension, ['mp4', 'webm', 'mov', 'avi'])) {
        $type = "video";
    }
}
$stmnt2->close();

// Get all posts for dashboard
$stmnt3 = $conn->prepare("SELECT post_type, post_content FROM posts ORDER BY created_at");
$stmnt3->execute();
$result3 = $stmnt3->get_result();

$allpost = $vedioarray = $story = [];
while ($row = $result3->fetch_assoc()) {
    if ($row["post_type"] != "story") {
        $allpost[] = $row["post_content"];
    }
    if ($row['post_type'] == "video") {
        $vedioarray[] = $row['post_content'];
    }
    if ($row['post_type'] == "story") {
        $story[] = $row['post_content'];
    }
}

$joinedstoryhtml = !empty($story) ? implode("", array_reverse($story)) : "";
$joinedpostHTML = !empty($allpost) ? implode("", array_reverse($allpost)) : "";
$joinedVedioHTML = !empty($vedioarray) ? implode("", array_reverse($vedioarray)) : "";
$stmnt3->close();
$id1;
// Get story data
$stmnt4 = $conn->prepare("SELECT story_id, image_url, video_url, audio_url, text_content FROM story WHERE user_id=? ORDER BY created_at DESC LIMIT 1");
$stmnt4->bind_param("i", $user_id);
$stmnt4->execute();
$result4 = $stmnt4->get_result();

if ($row = $result4->fetch_assoc()) {
    $stroyimg = htmlspecialchars($row['image_url']);
    $stroyvideo = htmlspecialchars($row['video_url']);
    $stroyaudio = htmlspecialchars($row['audio_url']);
    $stroytext = htmlspecialchars($row['text_content']);
    $id1=$row['story_id'];
}
$stmnt4->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="dashbordcss.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
</head>
<body>
    <div class="hole">
        <div class="nav">
            <div class="navline">
                <div class="navtext">BondhuBook</div>
                <div class="navsearch">
                    <input type="text" id="search" placeholder="Search Your BondhuBook"/>
                </div>
                <div class="bbms">
                    <img src="images/file_0000000042e061f8bf3c2680656c96e5 (1).png" alt="">
                </div>
            </div>
            <div class="navbar">
                <div class="profile" id="<?php echo htmlspecialchars($user_id); ?>">
                    <button>
                        <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile Picture">
                    </button>
                </div>
                <div class="home">
                    <button>
                        <img src="icon/home.png">
                    </button>
                </div>
                <div class="friends">
                    <button>
                        <img src="icon/friends.png" alt="">
                    </button>
                </div>
                <div class="vedio">
                    <button>
                        <img src="icon/video-camera.png" alt="">
                    </button>
                </div>
                <div class="notification">

                    <button id='notibutton'>
                        <div class="notificationcount">
                        </div>
                        <img src="icon/notification.png" alt="">
                        
                    </button>
                </div>
                <div class="msg">
                    <button>
                        <img src="icon/talk.png" alt="">
                    </button>
                </div>
                <div class="menu">
                    <button>
                        <img src="icon/app.png" alt="">
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="main">
            <div class="post-box">
                <div class="profile1">
                    <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile Picture">
                </div>
                <textarea placeholder="What's on your mind <?php echo htmlspecialchars($user_name);?> ?" rows="4"></textarea>
                <button id="dashbutton">Creat</button>
            </div>
            <div class="post">
                <div class="userprofilestory">
                    <button id="story">
                        <div class="story">
                            <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile Picture">
                        </div>
                        <div class="creat">
                            <img src="icon/plus.png" alt="">
                        </div>
                    </button>
                </div>
                <div class="creator"></div>
            </div>
            <div class="dashslot"></div>
        </div>
    </div>

<script>
var fixedid=<?=json_encode($user_id);?> ;
const nav = document.querySelector(".hole");
nav.style.maxWidth = "100%";
const msg = document.querySelector(".msg");
msg.addEventListener("click", function() {
    document.body.innerHTML = "";
    document.body.style.height = "100vh";
    document.body.style.margin = "0";
    document.body.style.padding = "0";
    document.body.style.backgroundColor = "#f0f2f5";
    document.body.style.display = "flex";
    document.body.style.flexDirection = "column";

    // Main container
    let selectContainer = document.createElement("div");
    selectContainer.style.maxWidth = "100%";
    selectContainer.style.height = "20vh";
    selectContainer.style.overflowY = "auto";
    selectContainer.style.backgroundColor = "gray";
    selectContainer.style.border = "1px solid #ccc";
    selectContainer.style.borderRadius = "10px 10px 0 0";
    selectContainer.style.boxShadow = "0 2px 8px rgba(0, 0, 0, 0.1)";
    selectContainer.style.padding = "20px";
    selectContainer.style.display = "flex";
    selectContainer.style.flexDirection = "column";
    selectContainer.style.gap = "15px";
    selectContainer.style.fontFamily = "Arial, sans-serif";

    // Profile section
    const profile = document.createElement("div");
    profile.style.height = "100px";
    profile.style.display = "flex";
    profile.style.alignItems = "center";

    const settings = document.createElement("div");
    settings.style.height = "100%";
    settings.style.width = "150px";
    settings.style.display = "flex";
    settings.style.flexDirection = "column";
    settings.style.background = "gray";

    const logo = document.createElement('img');
    logo.src = "images/file_0000000042e061f8bf3c2680656c96e5 (1).png";
    logo.style.height = "50px";
    logo.style.width = "50px";
    logo.style.borderRadius = "50%";
    settings.appendChild(logo);

    const app = document.createElement("img");
    app.src = "icon/app.png";
    app.style.height = "40px";
    app.style.width = "40px";

    const button = document.createElement("button");
    button.style.height = "40px";
    button.style.width = "40px";
    button.style.marginTop = "25px";
    button.style.marginLeft = "5px";
    button.style.display = "flex";
    button.style.alignItems = "center";
    button.style.justifyContent = "center";
    button.appendChild(app);
    settings.appendChild(button);
    profile.appendChild(settings);

    const searchbar = document.createElement("input");
    searchbar.type = "text";
    searchbar.style.width = "400px";
    searchbar.style.height = "30px";
    searchbar.placeholder = "Search your Bondhu !";
    searchbar.style.borderRadius = "10px";
    searchbar.style.marginLeft = "20%";
    searchbar.style.fontSize = "1rem";
    profile.appendChild(searchbar);

    const text = document.createElement("div");
    text.textContent = "Messages";
    text.style.color = "#ff0000";
    text.style.fontWeight = "bold";
    text.style.fontSize = "1rem";
    text.style.marginLeft = "20%";
    profile.appendChild(text);

    selectContainer.appendChild(profile);
    document.body.appendChild(selectContainer);

    // Full-width bottom div
    const alldiv = document.createElement("div");
    alldiv.style.maxWidth = "100%";
    alldiv.style.flex = "1";
    alldiv.style.background = "#111";

    const friendsdiv = document.createElement("div");
    friendsdiv.style.width = "100%";
    friendsdiv.style.height = "100px";
    friendsdiv.style.display="flex";
    const msgprofile = document.createElement("div");
    msgprofile.style.width = "115px";
    msgprofile.style.height = "100%";
    msgprofile.style.background = "white";
    msgprofile.style.border = "2px solid blue";
    msgprofile.style.borderRadius = "50%";
    msgprofile.style.marginLeft = "10px";
    msgprofile.style.marginRight = "30px";
    msgprofile.style.display = "flex";
    msgprofile.style.justifyContent = "center";
    msgprofile.style.alignItems = "center";
    msgprofile.style.position = "relative"; // <-- container relative

    const profilepic = document.createElement("img");
    profilepic.src = <?php echo json_encode($profile_image_url);?>; 
    profilepic.style.height = "100%";
    profilepic.style.width = "100%";
    profilepic.style.borderRadius = "50%";
    msgprofile.appendChild(profilepic);

    // Active dot (bottom-right)
    const active = document.createElement("div");
    active.style.height = "15px";
    active.style.width = "15px";
    active.style.backgroundColor = "lightgreen";
    active.style.border = "2px solid white";
    active.style.borderRadius = "50%";
    active.style.position = "absolute";  // <-- position absolute
    active.style.bottom = "5px";         // <-- from bottom
    active.style.right = "5px";          // <-- from right
    msgprofile.appendChild(active);

    const activefriends=document.createElement("div");
    activefriends.style.height = "100%";
    activefriends.style.width = "100%";
    activefriends.style.display="flex";
    friendsdiv.appendChild(msgprofile);
    fetch("messenger.php",{
        method:"POST",
        credentials:"same-origin"
    }).then(res=>res.json())
    .then(data=>{
        if(!data){
            alert("data not found from php");
        }else{
            data.friends.forEach(friend=>{
                const msgprofile = document.createElement("div");
                msgprofile.id=friend.id;
                msgprofile.style.width = "100px";
                msgprofile.style.height = "100%";
                msgprofile.style.background = "white";
                msgprofile.style.border = "2px solid blue";
                msgprofile.style.borderRadius = "50%";
                msgprofile.style.marginLeft = "10px";
                msgprofile.style.marginRight = "30px";
                msgprofile.style.display = "flex";
                msgprofile.style.justifyContent = "center";
                msgprofile.style.alignItems = "center";
                msgprofile.style.position = "relative"; // <-- container relative

                const profilepic = document.createElement("img");
                profilepic.src = friend.avatar; 
                profilepic.style.height = "100%";
                profilepic.style.width = "100%";
                profilepic.style.borderRadius = "50%";
                msgprofile.appendChild(profilepic);
                const active = document.createElement("div");
                active.style.height = "15px";
                active.style.width = "15px";
                active.style.backgroundColor = "lightgreen";
                active.style.border = "2px solid white";
                active.style.borderRadius = "50%";
                active.style.position = "absolute";  // <-- position absolute
                active.style.bottom = "5px";         // <-- from bottom
                active.style.right = "5px";          // <-- from right
                msgprofile.appendChild(active);
                activefriends.appendChild(msgprofile);
            })
        friendsdiv.appendChild(activefriends);
        alldiv.appendChild(friendsdiv);
        data.friends.forEach(friend=>{
            const msgfriends=document.createElement("div");
            msgfriends.id=friend.id;
            msgfriends.style.height = "82px";
            msgfriends.style.maxWidth = "95%";
            msgfriends.style.background="gray";
            msgfriends.style.marginTop="15px";
            msgfriends.style.marginLeft="15px";
            msgfriends.style.borderRadius="5px";
            msgfriends.style.display="flex";
            msgfriends.style.alignItems="center";
            const friendpicdiv=document.createElement("div");
            friendpicdiv.style.height = "70px";
            friendpicdiv.style.width = "70px";
            friendpicdiv.style.border="2px solid blue";
            friendpicdiv.style.borderRadius="50%";
            friendpicdiv.style.marginLeft="10px";
            friendpicdiv.style.position ="relative";
            const friendppic=document.createElement("img");
            friendppic.src=friend.avatar;
            friendppic.style.borderRadius="50%";
            friendppic.style.height = "70px";
            friendppic.style.width = "70px";
            friendpicdiv.appendChild(friendppic);
            const active = document.createElement("div");
            active.style.height = "15px";
            active.style.width = "15px";
            active.style.backgroundColor = "lightgreen";
            active.style.border = "2px solid white";
            active.style.borderRadius = "50%";
            active.style.position = "absolute";
            active.style.bottom = "3px";  // <--- added
            active.style.right = "3px";  // <-- position absolute 
            friendpicdiv.appendChild(active);
            const namedive=document.createElement("div");
            const name=document.createElement("div");
            name.innerText=friend.name;
            const status=document.createElement("div");
            status.style.marginTop="20px";
            status.style.marginLeft="20px";
            const seenform=new FormData();
            seenform.append("sender",msgfriends.id)
            fetch("seenorunseen.php",{
                method:"POST",
                body:seenform,
                credentials:"same-origin"
            }).then(response=>response.json())
            .then(data=>{
                if(data.data==0){
                    console.log(data.data);
                    status.innerText="Read";
                    status.style.color="white";
                    status.style.fontWeight="0.5 rem bold";
                }else{
                    status.innerText=data.data+"Unread messages";
                    status.style.color="blue";
                    status.style.fontWeight="0.9 rem bold";
                }
            })

            namedive.appendChild(name);
            namedive.appendChild(status);
            msgfriends.appendChild(friendpicdiv);
            const time = document.createElement("div");
            time.style.background = "white";
            time.style.fontSize = "0.9rem";
            time.style.color = "#555";
            time.style.padding = "5px 10px";
            time.style.borderRadius = "5px";
            time.innerText = "10:45 AM";
            time.style.marginLeft="70%";
            msgfriends.appendChild(namedive);
            msgfriends.appendChild(time);
            alldiv.appendChild(msgfriends);
            msgfriends.addEventListener("click",function(){
                msgfriendsslot(friend.name,friend.avatar,msgfriends.id);
                const seenform=new FormData();
                seenform.append('sender',msgfriends.id);
                fetch("seenmsg.php",{
                    method:"POST",
                    body:seenform,
                    credentials:"same-origin"
                }).then(response=>response.json())
                    .then(data=>{
                    })
                
                })
            })
            }
        })
    
    document.body.appendChild(alldiv);
});
function msgfriendsslot(friendsname,friendsavatar,msgfriendsid){
    document.body.innerHTML="";
    document.body.style.margin = "0";
    document.body.style.padding = "0";
    document.body.style.fontFamily = "Arial, sans-serif";
    document.body.style.background = "#f0f2f5";
    document.body.style.display = "flex";
    document.body.style.flexDirection = "column";
    document.body.style.height = "100vh";
    const header = document.createElement("div");
    header.style.height = "70px";
    header.style.background = "#fff";
    header.style.display = "flex";
    header.style.alignItems = "center";
    header.style.justifyContent = "space-between";
    header.style.padding = "0 20px";
    header.style.boxShadow = "0 2px 5px rgba(0,0,0,0.1)";
    const profileInfo = document.createElement("div");
    profileInfo.style.display = "flex";
    profileInfo.style.alignItems = "center";

    const profilePic = document.createElement("img");
    profilePic.src = friendsavatar;
    profilePic.style.width = "50px";
    profilePic.style.height = "50px";
    profilePic.style.borderRadius = "50%";
    profilePic.style.marginRight = "10px";

    const profileDetails = document.createElement("div");
    const profileName = document.createElement("div");
    profileName.textContent = friendsname;
    profileName.style.fontWeight = "bold";
    profileName.style.fontSize = "1.1rem";

    const profileTime = document.createElement("div");
    profileTime.textContent = "Active now";
    profileTime.style.fontSize = "0.8rem";
    profileTime.style.color = "#555";

    profileDetails.appendChild(profileName);
    profileDetails.appendChild(profileTime);
    profileInfo.appendChild(profilePic);
    profileInfo.appendChild(profileDetails);
    header.appendChild(profileInfo);

    const callButtons = document.createElement("div");
    callButtons.style.display = "flex";
    callButtons.style.gap = "10px";

    const audioCallBtn = document.createElement("button");
    audioCallBtn.textContent = "📞";
    audioCallBtn.style.fontSize = "1.5rem";
    audioCallBtn.style.border = "none";
    audioCallBtn.style.background = "transparent";
    audioCallBtn.style.cursor = "pointer";

    const videoCallBtn = document.createElement("button");
    videoCallBtn.textContent = "🎥";
    videoCallBtn.style.fontSize = "1.5rem";
    videoCallBtn.style.border = "none";
    videoCallBtn.style.background = "transparent";
    videoCallBtn.style.cursor = "pointer";

    callButtons.appendChild(audioCallBtn);
    callButtons.appendChild(videoCallBtn);
    header.appendChild(callButtons);
    document.body.appendChild(header);

    // ---------- MESSAGE AREA ----------
    const messageArea = document.createElement("div");
    messageArea.style.flex = "1";
    messageArea.style.overflowY = "auto";
    messageArea.style.padding = "20px";
    messageArea.style.display = "flex";
    messageArea.style.flexDirection = "column";
    messageArea.style.gap = "10px";
    document.body.appendChild(messageArea);
    const form=new FormData();
    form.append("receiver",msgfriendsid);
    fetch("get_msg.php",{
        method:"POST",
        body:form,
        credentials:"same-origin"
    })
        .then(response => response.json())
        .then(data => {
            console.log(data);  // <--- Add this
            if (data.success && Array.isArray(data.messages) && data.messages.length > 0) {
                const baseURL= "uploads/";
            data.messages.forEach(m => {if(m.type!="text"){ 
                addMessage1(m.content, m.type, m.sender,baseURL);
                function addMessage1(content, type = "text", sender,baseURL) {
                    messageArea.scrollTop = messageArea.scrollHeight;
                    const wrapper = document.createElement("div");
                    wrapper.style.display = "flex";
                    wrapper.style.margin = "10px 0";
                    wrapper.style.justifyContent = sender === "me" ? "flex-end" : "flex-start";
                    const msgContainer = document.createElement("div");
                    msgContainer.style.display = "flex";
                    msgContainer.style.flexDirection = "column";
                    msgContainer.style.alignItems = sender === "me" ? "flex-end" : "flex-start";

                    const msg = document.createElement("div");
                    msg.style.maxWidth = "60%";
                    msg.style.padding = "10px";
                    msg.style.borderRadius = "10px";
                    msg.style.wordBreak = "break-word";
                    msg.style.boxShadow = "0 1px 3px rgba(0,0,0,0.1)";
                    msg.style.background = sender === "me" ? "#d1f7c4" : "#fff";
                    if (type === "text") {
                        msg.textContent = content;

                    } else if (type === "image") {
                        const img = document.createElement("img");
                        img.src = baseURL+content;
                        img.style.maxWidth = "100%";
                        img.style.borderRadius = "8px";
                        msg.appendChild(img);
                    } else if (type === "audio") {
                        const audio = document.createElement("audio");
                        audio.src =baseURL +content;
                        audio.controls = true;
                        msg.appendChild(audio);
                    } else if (type === "video") {
                        const video = document.createElement("video");
                        video.src = baseURL+content;
                        video.controls = true;
                        video.style.maxWidth = "100%";
                        msg.appendChild(video);
                    }

                    msgContainer.appendChild(msg);
                    wrapper.appendChild(msgContainer);
                    messageArea.appendChild(wrapper);
                    
                    }
            }else {
                addMessage(m.content, m.type, m.sender);
            }});
        }else {
                alert("Error: " + (data.error || "No messages returned"));
            }
        })
        .catch(error => {
            alert("An error occurred: " + error);
        });

    // ---------- MESSAGE BUBBLE FUNCTION ----------
    function addMessage(content, type = "text", sender = "me") {
        
            messageArea.scrollTop = messageArea.scrollHeight;
        const wrapper = document.createElement("div");
        wrapper.style.display = "flex";
        wrapper.style.margin = "10px 0";
        wrapper.style.justifyContent = sender === "me" ? "flex-end" : "flex-start";
        const msgContainer = document.createElement("div");
        msgContainer.style.display = "flex";
        msgContainer.style.flexDirection = "column";
        msgContainer.style.alignItems = sender === "me" ? "flex-end" : "flex-start";

        const msg = document.createElement("div");
        msg.style.maxWidth = "60%";
        msg.style.padding = "10px";
        msg.style.borderRadius = "10px";
        msg.style.wordBreak = "break-word";
        msg.style.boxShadow = "0 1px 3px rgba(0,0,0,0.1)";
        msg.style.background = sender === "me" ? "#d1f7c4" : "#fff";
        if (type === "text") {
            msg.textContent = content;

        } else if (type === "image") {
            const img = document.createElement("img");
            img.src = content;
            img.style.maxWidth = "100%";
            img.style.borderRadius = "8px";
            msg.appendChild(img);
        } else if (type === "audio") {
            const audio = document.createElement("audio");
            audio.src =content;
            audio.controls = true;
            msg.appendChild(audio);
        } else if (type === "video") {
            const video = document.createElement("video");
            video.src = content;
            video.controls = true;
            video.style.maxWidth = "100%";
            msg.appendChild(video);
        }

        msgContainer.appendChild(msg);
        wrapper.appendChild(msgContainer);
        messageArea.appendChild(wrapper);
        
    }
    const footer = document.createElement("div");
    footer.style.height = "70px";
    footer.style.background = "#fff";
    footer.style.display = "flex";
    footer.style.alignItems = "center";
    footer.style.padding = "0 10px";
    footer.style.gap = "10px";
    footer.style.boxShadow = "0 -2px 5px rgba(0,0,0,0.1)";

    // Text input
    const textInput = document.createElement("textarea");
    textInput.type = "textarea";
    textInput.placeholder = "Type a message...";
    textInput.style.flex = "1";
    textInput.style.padding = "10px";
    textInput.style.border = "1px solid #ccc";
    textInput.style.borderRadius = "20px";

    // Send button
    const sendBtn = document.createElement("button");
    sendBtn.textContent = "➤";
    sendBtn.style.fontSize = "1.5rem";
    sendBtn.style.border = "none";
    sendBtn.style.background = "transparent";
    sendBtn.style.cursor = "pointer";
    sendBtn.onclick = () => {
        if (textInput.value!== "") {
            const baseUrl='';
            addMessage(textInput.value, "text");
        const msgform=new FormData();
        msgform.append('receiver',msgfriendsid);
        msgform.append('type',"text");
        msgform.append('content',textInput.value);
        fetch("messege_sent.php",{
            method:"POST",
            body:msgform,
            credentials:"same-origin"
        }).then(response=>response.json())
        .then(data=>{
            if(!data){
                    alert("not successfull");
            }else{
                alert("successfull")
            }
            
        }).catch(error=>{
            alert("error",data.error);
        })
            textInput.value = "";
            
        }
    };
    const plusBtn = document.createElement("button");
    plusBtn.textContent = "➕";
    plusBtn.style.fontSize = "1.8rem";
    plusBtn.style.border = "none";
    plusBtn.style.background = "transparent";
    plusBtn.style.cursor = "pointer";

    // Menu popup
    const menu = document.createElement("div");
    menu.style.position = "absolute";
    menu.style.bottom = "80px";
    menu.style.left = "10px";
    menu.style.background = "#fff";
    menu.style.padding = "10px";
    menu.style.borderRadius = "8px";
    menu.style.boxShadow = "0 2px 5px rgba(0,0,0,0.2)";
    menu.style.display = "none";
    menu.style.flexDirection = "column";
    menu.style.gap = "10px";

    const cameraPicBtn = document.createElement("button");
    cameraPicBtn.textContent = "📸 Take Photo";
    cameraPicBtn.style.cursor = "pointer";

    const cameraVideoBtn = document.createElement("button");
    cameraVideoBtn.textContent = "🎥 Record Video";
    cameraVideoBtn.style.cursor = "pointer";

    const recordAudioBtn = document.createElement("button");
    recordAudioBtn.textContent = "🎤 Record Audio";
    recordAudioBtn.style.cursor = "pointer";

    const galleryBtn = document.createElement("button");
    galleryBtn.textContent = "🖼️ Gallery";
    galleryBtn.style.cursor = "pointer";

    menu.appendChild(cameraPicBtn);
    menu.appendChild(cameraVideoBtn);
    menu.appendChild(recordAudioBtn);
    menu.appendChild(galleryBtn);

    document.body.appendChild(menu);

    plusBtn.onclick = () => {
        menu.style.display = menu.style.display === "none" ? "flex" : "none";
    };

    // Camera overlay for photo/video
    function openCameraOverlay(mode) {
        const overlay = document.createElement("div");
        overlay.style.position = "fixed";
        overlay.style.top = "0";
        overlay.style.left = "0";
        overlay.style.width = "1000px";
        overlay.style.height = "500px";
        overlay.style.background = "#000";
        overlay.style.display = "flex";
        overlay.style.flexDirection = "column";
        overlay.style.alignItems = "center";
        overlay.style.justifyContent = "space-between";
        overlay.style.zIndex = "9999";
        overlay.style.padding = "20px";

        const videoPreview = document.createElement("video");
        videoPreview.autoplay = true;
        videoPreview.style.width = "100%";
        videoPreview.style.flex = "1";
        overlay.appendChild(videoPreview);

        const controls = document.createElement("div");
        controls.style.display = "flex";
        controls.style.justifyContent = "space-around";
        controls.style.alignItems = "center";
        controls.style.width = "100%";
        controls.style.padding = "20px";

        const flashBtn = document.createElement("button");
        flashBtn.textContent = "⚡";
        flashBtn.style.fontSize = "1.8rem";
        flashBtn.style.background = "transparent";
        flashBtn.style.color = "#fff";
        flashBtn.style.border = "none";
        flashBtn.style.marginBottom="20%";
        

        const captureBtn = document.createElement("button");
        captureBtn.style.width = "100px";
        captureBtn.style.height = "100px";
        captureBtn.style.borderRadius = "50%";
        captureBtn.style.marginBottom="20%";
        captureBtn.style.background = "#fff";
        captureBtn.style.border = "5px solid #ccc";
        captureBtn.style.cursor = "pointer";

        navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" }, audio: mode === "video" })
            .then(stream => {
                videoPreview.srcObject = stream;
                let mediaRecorder;
                let chunks = [];
                if (mode === "video") {
                    captureBtn.onclick = () => {
                        if (!mediaRecorder || mediaRecorder.state === "inactive") {
                            mediaRecorder = new MediaRecorder(stream);
                            chunks = [];
                            mediaRecorder.ondataavailable = e => chunks.push(e.data);
                            mediaRecorder.onstop = () => {
                                const blob = new Blob(chunks, { type: "video/mp4" });
                                addMessage(URL.createObjectURL(blob), "video");
                                document.body.removeChild(overlay);
                                stream.getTracks().forEach(track => track.stop());
                                const msgform = new FormData();
                                msgform.append('receiver', msgfriendsid);
                                msgform.append('type', "video");
                                msgform.append('content', blob, 'recording.mp4'); // send blob as file

                                fetch("messege_sent.php", {
                                    method: "POST",
                                    body: msgform,
                                    credentials: "same-origin"
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (!data || data.status !== "success") {
                                        alert("Not successful: " + (data.error || data.message));
                                    } else {
                                        alert("Successful");
                                    }
                                })
                                .catch(error => {
                                    alert("Error: " + error);
                                });
                            };
                            mediaRecorder.start();
                            captureBtn.style.background = "red";
                                
                        } else {
                            mediaRecorder.stop();
                        }
                    };
                } else {
                    captureBtn.onclick = () => {
                    const canvas = document.createElement("canvas");
                    canvas.width = videoPreview.videoWidth;
                    canvas.height = videoPreview.videoHeight;
                    canvas.getContext("2d").drawImage(videoPreview, 0, 0);
                    addMessage(canvas.toDataURL("image/png"), "image"); // preview

                    canvas.toBlob((blob) => {
                        const msgform = new FormData();
                        msgform.append('receiver', msgfriendsid);
                        msgform.append('type', "image");
                        msgform.append('content', blob, 'capture.png'); // send blob as file

                        fetch("messege_sent.php", {
                            method: "POST",
                            body: msgform,
                            credentials: "same-origin"
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (!data || data.status !== "success") {
                                alert("Not successful: " + (data.error || data.message));
                            } else {
                                alert("Successful");
                            }
                        })
                        .catch(error => {
                            alert("Error: " + error);
                        });
                    }, "image/png");
                };

                }
            });
            
        controls.appendChild(flashBtn);
        controls.appendChild(captureBtn);
        overlay.appendChild(controls);
        document.body.appendChild(overlay);
    }

    cameraPicBtn.onclick = () => openCameraOverlay("photo");
    cameraVideoBtn.onclick = () => openCameraOverlay("video");
    let mediaRecorder;
    let audioChunks = [];
    recordAudioBtn.onclick = async () => {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
        mediaRecorder.onstop = () => {
            const audioBlob = new Blob(audioChunks, { type: 'audio/mpeg' });
            addMessage(URL.createObjectURL(audioBlob), "audio");
            const msgform = new FormData();
            msgform.append('receiver', msgfriendsid);
            msgform.append('type', "audio");
            msgform.append('content', audioBlob, 'recording.mp3'); // send blob as file

            fetch("messege_sent.php", {
                method: "POST",
                body: msgform,
                credentials: "same-origin"
            })
            .then(response => response.json())
            .then(data => {
                if (!data || data.status !== "success") {
                    alert("Not successful: " + (data.error || data.message));
                } else {
                    alert("Successful");
                }
            })
            .catch(error => {
                alert("Error: " + error);
            });

        };
        mediaRecorder.start();
        setTimeout(() => mediaRecorder.stop(), 5000);
    };
    galleryBtn.onclick = () => {
        const input = document.createElement("input");
        input.type = "file";
        input.accept = "image/*,video/*";
        input.onchange = (e) => {
            const file = e.target.files[0];
            const reader = new FileReader();
            reader.onload = () => {
                if (file.type.startsWith("image/")) {
                    addMessage(reader.result, "image");
                    const msgform=new FormData();
                    msgform.append('receiver',msgfriendsid);
                    msgform.append('type',"image");
                    msgform.append('content',file);
                    fetch("messege_sent.php",{
                        method:"POST",
                        body:msgform,
                        credentials:"same-origin"
                    }).then(response=>response.json())
                    .then(data=>{
                        if(!data){
                            alert("not successfull");
                        }else{
                            alert("successfull")
                        }
                    
                    }).catch(error=>{
                        alert("error",data.error);
                    })

                }
                else if(file.type.startsWith("video/")) {
                    addMessage(reader.result, "video");
                    const msgform=new FormData();
                    msgform.append('receiver',msgfriendsid);
                    msgform.append('type',"video");
                    msgform.append('content',file);
                    fetch("messege_sent.php",{
                        method:"POST",
                        body:msgform,
                        credentials:"same-origin"
                    }).then(response=>response.json())
                    .then(data=>{
                        if(!data){
                            alert("not successfull");
                        }else{
                            alert("successfull")
                        }
                    
                    }).catch(error=>{
                        alert("error",data.error);
                    })
                }
            };
            reader.readAsDataURL(file);
        };
        input.click();
    };
    footer.appendChild(plusBtn);
    footer.appendChild(textInput);
    footer.appendChild(sendBtn);
    document.body.appendChild(footer);
}
const vedio1 = document.querySelector(".vedio");
vedio1.addEventListener("click", function () {
    document.body.innerHTML = "";
    document.body.style.cssText = `
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        background-color: #f0f2f5;
        height: 100vh;
        overflow: hidden;
        font-family: Arial, sans-serif;
    `;
    const selectContainer = document.createElement("div");
    selectContainer.style.cssText = `
        width: 100%;
        background-color: white;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    `;
    selectContainer.appendChild(nav);

    // Video feed wrapper
    const feedScrollArea = document.createElement("div");
    feedScrollArea.style.cssText = `
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        background-color: #f0f2f5;
        display: flex;
        flex-direction: column;
        gap: 20px;
    `;

    // Create div and fill with video feed HTML
    const vedio3 = document.createElement("div");
    vedio3.style.cssText = `
        display: flex;
        flex-direction: column;
        gap: 20px;
    `;
    feedScrollArea.appendChild(vedio3);
    selectContainer.appendChild(feedScrollArea);
    document.body.appendChild(selectContainer);
    fetch('dasbordpost.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success === false) {
                console.error(data.message);
                return;
            }
            vedio3.innerHTML =data.vedios;
            const likeButtons = vedio3.querySelectorAll(".Like"); 
            likeButtons.forEach(button => { 
            const userId = <?php echo json_encode($user_id); ?>; 

            likeButtons.forEach(button => {
            const creatorId = button.id;

            button.style.fontSize = "1.5rem";
            button.style.position = "relative";
            button.style.overflow = "visible";

            const allreact = document.createElement("div");
            allreact.style.display = 'flex';
            allreact.style.position = "absolute";
            allreact.style.bottom = "100%";
            allreact.style.gap = "10px";

            allreact.innerHTML = `
                <button class="story-reaction" data-reaction="❤️" value="L" style="font-size:1.5rem;">❤️</button>
                <button class="story-reaction" data-reaction="😂" value="H" style="font-size:1.5rem;">😂</button>
                <button class="story-reaction" data-reaction="😮" value="W" style="font-size:1.5rem;">😮</button>
                <button class="story-reaction" data-reaction="😢" value="S" style="font-size:1.5rem;">😢</button>
            `;

            const btns = allreact.querySelectorAll(".story-reaction");

            button.addEventListener("mouseover", function () {
                this.style.backgroundColor = "gray";
                if (!button.contains(allreact)) {
                    button.appendChild(allreact);
                }
            });

            button.addEventListener("mouseout", function () {
                this.style.backgroundColor = "";
            });
            let type;
            const form = new FormData();
            form.append("user_id", userId);

            fetch(`get_like.php?creator=${creatorId}`, {
                method: "POST",
                body: form
            })
            .then(response => response.json())
            .then(data => {
                console.log("Response:", data);

                if (data.error) {
                    console.error("Error:", data.error);
                    return;
                }

                let likeclick = data.isgiven? 0 : 1;
                button.innerText = (data.isgiven === "L" ? "❤️" : data.isgiven === "H" ? "😂" : data.isgiven === "W" ? "😮" : data.isgiven === "S" ? "😢" : "👍") + " " + data.like_count;

                // ✅ প্রতিটি রিয়্যাকশন বাটনের জন্য ক্লিক ইভেন্ট
                btns.forEach(rbtn => {
                    rbtn.addEventListener("click", function (e) {
                        e.stopPropagation();
                        button.appendChild(allreact);
                        const reaction = this.dataset.reaction;
                        type = this.value;
                        const form = new FormData();
                        form.append("like", 1);
                        form.append("creator", creatorId);
                        form.append("userid", userId);
                        form.append("type", type);
                        
                        fetch("like.php", {
                            method: "POST",
                            body: form,
                            credentials: "same-origin"
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                button.innerText = reaction + " " + data.like_count;
                                likeclick = 0;
                                console.log("Reaction saved!");
                            } else {
                                alert("Failed: " + data.message);
                            }
                        })
                        .catch(error => {
                            console.error("Error:", error);
                        });
                    });
                });
                button.addEventListener("click", function () {
                    if (likeclick === 1) return; // নতুন লাইক না থাকলে কিছু করবেন না

                    const form = new FormData();
                    form.append("like", -1);
                    form.append("creator", creatorId);
                    form.append("userid", userId);
                    form.append("type", type);

                    fetch("like.php", {
                        method: "POST",
                        body: form,
                        credentials: "same-origin"
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            button.innerText = "👍 " ;
                            likeclick = 1;
                            console.log("Unliked successfully");
                        } else {
                            alert("Failed to unlike: " + data.message);
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                    });
                });
            });
            });

            })
            const commentButtons = vedio3.querySelectorAll(".Comment");
            commentButtons.forEach(button => {
            button.addEventListener("mouseover", function () {
                this.style.backgroundColor = "gray";
            });

            button.addEventListener("mouseout", function () {
                this.style.backgroundColor = "";
            });

            button.addEventListener("click", function () {
                commentsall(button.id);
            });
            });

            const shareButtons = vedio3.querySelectorAll(".Share"); 
            shareButtons.forEach(button => {
                button.addEventListener("mouseover", function() {
                    this.style.backgroundColor = "gray"; // Fixed typo "gary" to "gray"
                    
                });
                
                button.addEventListener("mouseout", function() {
                    this.style.backgroundColor = ""; 
                    // Revert to original color
                });
            });


            const seelike = vedio3.querySelectorAll(".seelike");

           seelike.forEach(button => {
            const id = button.id;
            const form = new FormData();
            form.append("id", id);
            
            fetch("seelike.php", {
                method: "POST",
                body: form
            })
            .then(response => response.json())
            .then(data => {
                console.log("Response react:", data);
                console.log("SERVER RESPONSE:", data); 

            if (data.error) {
                console.error("Error from server:", data.error);
                return;
            }

            if (!Array.isArray(data) || data.length === 0) {
                console.warn("No reactions to show");
                return;
            }


                button.addEventListener("mouseover", function () {
                    this.style.backgroundColor = "gray";
                });

                button.addEventListener("mouseout", function () {
                    this.style.backgroundColor = "";
                });

                button.addEventListener("click", function () {
                    const container = document.createElement("div");
                    container.style.position = "fixed";
                    container.style.top = "0";
                    container.style.left = "0";
                    container.style.width = "100vw";
                    container.style.height = "100vh";
                    container.style.background = "rgba(0, 0, 0, 0.6)";
                    container.style.zIndex = "9999";
                    container.style.display = "flex";
                    container.style.justifyContent = "center";
                    container.style.alignItems = "center";

                    const box = document.createElement("div");
                    box.style.background = "white";
                    box.style.padding = "20px";
                    box.style.borderRadius = "12px";
                    box.style.width = "360px";
                    box.style.maxHeight = "80vh";
                    box.style.overflowY = "auto";
                    box.innerHTML = `<h3 style="text-align:center;">Reactions</h3><hr>`;

                    data.forEach(user => {
                        const row = document.createElement("div");
                        row.style.display = "flex";
                        row.style.alignItems = "center";
                        row.style.marginBottom = "12px";

                        const img = document.createElement("img");
                        img.src = user.profile_picture;
                        img.style.width = "40px";
                        img.style.height = "40px";
                        img.style.borderRadius = "50%";
                        img.style.marginRight = "10px";;
                        img.addEventListener("click", function() {
                            const profileimgurl2 = user.profile_picture;
                            const user_id2 =user.other_id;
                            const id2 = user.other_id;
                            const username2 = user.name;
                            const form = new FormData();
                            form.append("user_id",user_id2); // FIXED: use noti.sender_id

                            fetch("fetch_content.php", {
                                method: "POST",
                                body: form,
                                credentials: "same-origin"
                            })
                            .then(response => response.json())
                            .then(data => {
                                const htmldata2 = data.posts;
                                profileclick(profileimgurl2, user_id2, id2, username2, htmldata2, fixedid);
                            })
                            .catch(err => console.error(err));
                        });

                        const name = document.createElement("span");
                        name.textContent = user.name;
                        name.style.flexGrow = "1";
                        name.style.fontWeight = "bold";

                        const react = document.createElement("span");
                        react.textContent =(user.reaction === "L" ? "❤️" : user.reaction === "H" ? "😂" : user.reaction === "W" ? "😮" : user.reaction === "S" ? "😢" : "👍");
                        react.style.fontSize = "20px";

                        row.appendChild(img);
                        row.appendChild(name);
                        row.appendChild(react);

                        box.appendChild(row);
                    });

                    const closeBtn = document.createElement("button");
                    closeBtn.textContent = "Close";
                    closeBtn.style.marginTop = "15px";
                    closeBtn.style.padding = "8px 16px";
                    closeBtn.style.cursor = "pointer";
                    closeBtn.addEventListener("click", () => container.remove());

                    box.appendChild(closeBtn);
                    container.appendChild(box);
                    document.body.appendChild(container);
                });
            })
            .catch(err => console.error("Fetch error:", err));
        }); 
        const creatreels=vedio3.querySelectorAll(".video");
        creatreels.forEach(button=>{
            button.addEventListener("click",function(){
                const mainid = button.id;
                const firstUserEl = document.querySelector(`#name${mainid}`);
                const firstTitleEl = document.querySelector(`#caption${mainid}`);
                const firstProfileEl = document.querySelector(`#profile_img${mainid}`);
                (function () {
                    let reels = [
                        { 
                            user: firstUserEl?.innerText || '', 
                            title: firstTitleEl?.innerText || '', 
                            src: button?.src || button?.getAttribute('data-src') || '', 
                            profile: firstProfileEl?.src || '' ,
                            likeid:mainid
                        }
                    ];
                    creatreels.forEach(reel => {
                        if(mainid!=reel.id){
                            const rid = reel.id;
                            const reelUserEl = document.querySelector(`#name${rid}`);
                            const reelTitleEl = document.querySelector(`#caption${rid}`);
                            const reelProfileEl = document.querySelector(`#profile_img${rid}`);
                            reels.push({
                            user: reelUserEl?.innerText || '',
                            title: reelTitleEl?.innerText || '',
                            src: reel?.src || reel?.getAttribute('data-src') || '',
                            profile: reelProfileEl?.src || '',
                            likeid:rid
                        });
                        }
                    });
                function el(tag, attrs = {}, styles = {}, children = []) {
                    const node = document.createElement(tag);
                    Object.keys(attrs).forEach(k => {
                    if (k === 'html') node.innerHTML = attrs[k];
                    else if (k === 'text') node.textContent = attrs[k];
                    else node.setAttribute(k, attrs[k]);
                    });
                    Object.assign(node.style, styles);
                    (Array.isArray(children) ? children : [children]).forEach(c => {
                    if (!c) return;
                    if (typeof c === 'string') node.appendChild(document.createTextNode(c));
                    else node.appendChild(c);
                    });
                    return node;
                }
                document.documentElement.style.height = '100%';
                document.body.innerHTML = '';
                Object.assign(document.body.style, {
                    margin: '0',
                    height: '100vh',
                    overflow: 'hidden',
                    fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial',
                    background: '#000',
                    color: '#fff',
                    WebkitFontSmoothing: 'antialiased'
                });
                const wrapper = el('div', {}, {
                    height: '100vh',
                    width: '100vw',
                    overflowY: 'auto',
                    scrollSnapType: 'y mandatory',
                    WebkitOverflowScrolling: 'touch',
                    position: 'relative'
                });
                const header = el('div', {}, {
                    position: 'fixed',
                    top: '12px',
                    left: '12px',
                    zIndex: '9999',
                    padding: '8px 12px',
                    background: 'rgba(0,0,0,0.35)',
                    borderRadius: '999px',
                    fontWeight: '700',
                    fontSize: '14px'
                }, ['Reels']);

                document.body.appendChild(header);
                document.body.appendChild(wrapper);
                reels.forEach((r, idx) => {
                    const reel = el('div', { 'data-id': `reel-${idx}` }, {
                    height: '100vh',
                    width: '100vw',
                    position: 'relative',
                    scrollSnapAlign: 'start',
                    display: 'flex',
                    justifyContent: 'center',
                    alignItems: 'center',
                    background: '#000'
                    });
                    const video = el('video', { src: r.src, playsinline: '', loop: '' }, {
                    height: '100vh',
                    width: '100vw',
                    objectFit: 'cover',
                    display: 'block',
                    background: '#111'
                    });
                    video.preload = 'metadata';
                    video.muted =false;
                    const infoBox = el('div', {}, {
                    position: 'absolute',
                    left: '14px',
                    bottom: '28px',
                    zIndex: '9000',
                    maxWidth: '66%',
                    textShadow: '0 6px 18px rgba(0,0,0,0.6)'
                    });

                    const avatar = el('div', {}, {
                    width: '44px',
                    height: '44px',
                    borderRadius: '999px',
                    display: 'inline-flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    fontWeight: '700',
                    marginRight: '10px',
                    verticalAlign: 'middle',
                    backgroundImage: `url(${r.profile})`,
                    backgroundSize: 'cover',
                    backgroundPosition: 'center'
                });


                    const userLine = el('div', {}, {
                    display: 'inline-block',
                    verticalAlign: 'middle',
                    fontSize: '15px',
                    fontWeight: '700'
                    }, ['@' + r.user]);

                    const caption = el('div', {}, {
                    marginTop: '8px',
                    fontSize: '15px',
                    lineHeight: '1.2',
                    opacity: '0.95'
                    }, [r.title]);

                    const userRow = el('div', {}, { display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '8px' }, [avatar, userLine]);
                    infoBox.appendChild(userRow);
                    infoBox.appendChild(caption);
                    const centerIcon = el('div', {}, {
                    position: 'absolute',
                    left: '50%',
                    top: '50%',
                    transform: 'translate(-50%,-50%)',
                    zIndex: '9100',
                    pointerEvents: 'none',
                    fontSize: '48px',
                    opacity: '0',
                    transition: 'opacity 180ms ease'
                    }, ['▶']);

                    const rightCol = el('div', {}, {
                    position: 'absolute',
                    right: '5%',
                    top: '40%',
                    zIndex: '9000',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '33px',
                    alignItems: 'center'
                    });

                   const like = document.createElement("div");
                    like.className="Like"; 
                    like.innerText = '👍';
                    like.style.display = "flex";
                    like.style.justifyContent = "center";
                    like.style.alignItems = "center";
                    like.style.cursor = "pointer";
                    like.style.marginRight="20%";
                    like.id=r.likeid;
                    like.style.fontSize="2rem";
                    

                    // Comment Button
                    const COMENT = document.createElement("div");
                    COMENT.className="Comment";
                    COMENT.innerText = '💬';
                    COMENT.id=r.likeid;
                    COMENT.style.display = "flex";
                    COMENT.style.justifyContent = "center";
                    COMENT.style.alignItems = "center";
                    COMENT.style.cursor = "pointer";
                    COMENT.style.marginRight="20%";
                    COMENT.style.fontSize="2rem";
                    // Share Button
                    const SHARE = document.createElement("div");
                    SHARE.className="Share";
                    SHARE.id=r.likeid;
                    SHARE.innerText = '↗️';
                    SHARE.style.display = "flex";
                    SHARE.style.justifyContent = "center";
                    SHARE.style.alignItems = "center";
                    SHARE.style.cursor = "pointer";
                    SHARE.style.marginLeft="20%";
                    SHARE.style.fontSize="2rem";
                    SHARE.style.marginRight="20%";
                    const allreact = document.createElement("div");
                    allreact.style.display = 'flex';
                    allreact.style.position = "absolute";
                    allreact.style.bottom = "100%";
                    allreact.style.gap = "10px";
                    allreact.style.marginRight="100%";
                    allreact.innerHTML = `
                        <button class="story-reaction" data-reaction="❤️" value="L" style="font-size:1rem;">❤️</button>
                        <button class="story-reaction" data-reaction="😂" value="H" style="font-size:1rem;">😂</button>
                        <button class="story-reaction" data-reaction="😮" value="W" style="font-size:1rem;">😮</button>
                        <button class="story-reaction" data-reaction="😢" value="S" style="font-size:1rem;">😢</button>
                    `;
                     let type;
                    const btns = allreact.querySelectorAll(".story-reaction");

                    like.addEventListener("mouseover", function () {
                        this.style.backgroundColor = "gray";
                        if (!like.contains(allreact)) {
                            like.appendChild(allreact);
                        }
                    });

                    like.addEventListener("mouseout", function () {
                        this.style.backgroundColor = "";
                    });

                    const userid=<?php echo json_encode($user_id);?>;
                     const form = new FormData();
                    form.append("user_id",userid);

                    fetch(`get_like.php?creator=${like.id}`, {
                        method: "POST",
                        body: form
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log("Response:", data);

                        if (data.error) {
                            console.error("Error:", data.error);
                            return;
                        }

                        let likeclick = data.isgiven? 0 : 1;
                        like.innerText = (data.isgiven === "L" ? "❤️" : data.isgiven === "H" ? "😂" : data.isgiven === "W" ? "😮" : data.isgiven === "S" ? "😢" : "👍") + " " + data.like_count;
                            btns.forEach(rbtn => {
                           rbtn.addEventListener("click", function (e) {
                            e.stopPropagation();
                            button.appendChild(allreact);
                            const reaction = this.dataset.reaction;
                            type = this.value;
                            const form = new FormData();
                            form.append("like", 1);
                            form.append("creator",like.id);
                            form.append("userid", userid);
                            form.append("type", type);
                            
                            fetch("like.php", {
                                method: "POST",
                                body: form,
                                credentials: "same-origin"
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    button.innerText = reaction + "" + data.like_count;
                                    likeclick = 0;
                                    console.log("Reaction saved!");
                                } else {
                                    alert("Failed: " + data.message);
                                }
                            })
                            .catch(error => {
                                console.error("Error:", error);
                            });
                        });
                        });
                        like.addEventListener("click", function () {
                        if (likeclick === 1) return; 
                        const form = new FormData();
                        form.append("like", -1);
                        form.append("creator", like.id);
                        form.append("userid", userid);
                        form.append("type", type);

                        fetch("like.php", {
                            method: "POST",
                            body: form,
                            credentials: "same-origin"
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                button.innerText = "👍 " ;
                                likeclick = 1;
                                console.log("Unliked successfully");
                            } else {
                                alert("Failed to unlike: " + data.message);
                            }
                        })
                        .catch(error => {
                            console.error("Error:", error);
                        });
                        });
                    });
                   
                    
                     COMENT.addEventListener("mouseover", function () {
                        this.style.backgroundColor = "gray";
                    });

                    COMENT.addEventListener("mouseout", function () {
                        this.style.backgroundColor = "";
                    });

                    COMENT.addEventListener("click", function () {
                        commentsall(COMENT.id);
                    });



                     reel.addEventListener('dblclick', () => {
                    video.muted = !video.muted;
                    centerIcon.textContent = video.muted ? '🔇' : '🔊';
                    centerIcon.style.opacity = '1';
                    setTimeout(() => (centerIcon.style.opacity = '0'), 600);
                    });
                    reel.addEventListener('click', () => {
                    if (video.paused) { video.play().catch(() => {}); centerIcon.textContent = '⏸'; }
                    else { video.pause(); centerIcon.textContent = '▶'; }
                    centerIcon.style.opacity = '1';
                    setTimeout(() => (centerIcon.style.opacity = '0'), 420);
                    });

                    rightCol.appendChild(like);
                    rightCol.appendChild(COMENT);
                    rightCol.appendChild(SHARE);

                    reel.appendChild(video);
                    reel.appendChild(infoBox);
                    reel.appendChild(centerIcon);
                    reel.appendChild(rightCol);

                    wrapper.appendChild(reel);
                });
                const observed = Array.from(wrapper.querySelectorAll('div[data-id]'));
                let currentVideo = null;
                const io = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                    const vid = entry.target.querySelector('video');
                    if (entry.intersectionRatio >= 0.75) {
                        if (currentVideo && currentVideo !== vid) {
                        try { currentVideo.pause(); } catch (e) {}
                        }
                        vid.play().catch(() => {});
                        currentVideo = vid;
                    } else if (entry.intersectionRatio < 0.5) {
                        try { vid.pause(); } catch (e) {}
                    }
                    });
                }, { root: wrapper, threshold: [0.5, 0.75, 0.9] });

                observed.forEach(n => io.observe(n));
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'ArrowDown' || e.key === 'PageDown') {
                    e.preventDefault();
                    wrapper.scrollBy({ top: window.innerHeight, behavior: 'smooth' });
                    } else if (e.key === 'ArrowUp' || e.key === 'PageUp') {
                    e.preventDefault();
                    wrapper.scrollBy({ top: -window.innerHeight, behavior: 'smooth' });
                    }
                });
                let touchActive = false;
                wrapper.addEventListener('touchstart', () => touchActive = true);
                wrapper.addEventListener('touchend', () => {
                    if (!touchActive) return;
                    touchActive = false;
                    const idx = Math.round(wrapper.scrollTop / window.innerHeight);
                    wrapper.scrollTo({ top: idx * window.innerHeight, behavior: 'smooth' });
                });
                setTimeout(() => {
                    const first = wrapper.querySelector('video');
                    if (first) first.play().catch(() => {});
                }, 300);
                })();

            })
        })
        })
        .catch(error => {
            console.error('Error:', error);
        });
    
    
});
const userpro=document.querySelector(".userprofilestory");
userpro.style.height="280px";
userpro.style.width="200px";
userpro.style.background="white";
const profileImageUrl = <?php echo json_encode($profile_image_url); ?>;
const name=<?php echo json_encode($user_name); ?>;
const hide = document.body.innerHTML;
const profile = document.querySelector('.profile');
let takeimgurl = "";
let selectedFile = null;
document.addEventListener('DOMContentLoaded', function () {
    const storyButton = document.getElementById('story');
    if (storyButton) {
        const set = document.querySelector(".creator");
        set.style.display = "flex";
        set.style.overflow = "scroll";
        fetch('dasbordpost.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success === false) {
                    console.error(data.message);
                    return;
                }
               set.innerHTML= data.story; 
                const videos = set.querySelectorAll('video');
                            videos.forEach(video => {
                                video.muted = true;
                                video.autoplay = true;
                                video.loop = true;});
               const storyshow = set.querySelectorAll(".storyel");
                storyshow.forEach((button, index) => {
                    button.addEventListener('click', function () {
                        const storyViewer = document.createElement('div');
                        storyViewer.style.position = 'fixed';
                        storyViewer.style.top = '0';
                        storyViewer.style.left = '0';
                        storyViewer.style.width = '100vw';
                        storyViewer.style.height = '100vh';
                        storyViewer.style.backgroundColor = 'black';
                        storyViewer.style.zIndex = '1000';
                        storyViewer.style.display = 'flex';
                        storyViewer.style.flexDirection = 'column';
                        storyViewer.style.overflow = 'hidden';

                        // Progress bar container
                        const progressContainer = document.createElement('div');
                        progressContainer.style.display = 'flex';
                        progressContainer.style.width = '96%';
                        progressContainer.style.gap = '4px';
                        progressContainer.style.margin = '10px auto';
                        progressContainer.style.position = 'relative';
                        storyshow.forEach((story, i) => {
                            const progressWrapper = document.createElement('div');
                            progressWrapper.style.flex = '1';
                            progressWrapper.style.height = '3px';
                            progressWrapper.style.backgroundColor = 'rgba(255,255,255,0.3)';
                            progressWrapper.style.borderRadius = '3px';
                            progressWrapper.style.overflow = 'hidden';

                            const progressBar = document.createElement('div');
                            progressBar.className = 'story-progress';
                            progressBar.style.height = '100%';
                            progressBar.style.width = i === index ? '0%' : (i < index ? '100%' : '0%');
                            progressBar.style.backgroundColor = '#ffffff';
                            progressBar.style.transition = 'width 0.1s linear';

                            progressWrapper.appendChild(progressBar);
                            progressContainer.appendChild(progressWrapper);
                        });

                        // Story content container
                        const storyContent = document.createElement('div');
                        storyContent.style.flex = '1';
                        storyContent.style.display = 'flex';
                        storyContent.style.justifyContent = 'center';
                        storyContent.style.alignItems = 'center';
                        storyContent.style.position = 'relative';
                        storyContent.style.width = '100%';

                        // Views counter
                        
                        // Close button
                        const closeBtn = document.createElement('button');
                        closeBtn.style.position = 'absolute';
                        closeBtn.style.top = '15px';
                        closeBtn.style.left = '15px';
                        closeBtn.style.background = 'rgba(0,0,0,0.3)';
                        closeBtn.style.border = 'none';
                        closeBtn.style.color = 'white';
                        closeBtn.style.fontSize = '24px';
                        closeBtn.style.width = '40px';
                        closeBtn.style.height = '40px';
                        closeBtn.style.borderRadius = '50%';
                        closeBtn.style.zIndex = '1001';
                        closeBtn.innerHTML = '✕';
                        closeBtn.addEventListener('click', () => {
                            document.body.removeChild(storyViewer);
                            clearInterval(progressInterval);
                            viewslot.style.right = "-300px";
                                    setTimeout(() => viewslot.remove(), 100);
                        });

                        storyViewer.appendChild(progressContainer);
                        storyViewer.appendChild(storyContent);
                        storyViewer.appendChild(closeBtn);
                        

                        // Progress + swipe vars
                        let currentStory = index;
                        const progressBars = progressContainer.querySelectorAll('.story-progress');
                        let progressInterval;

                        // Load Story Function
                        function loadStory(newIndex) {
                            if (newIndex < 0 || newIndex >= storyshow.length) return;
                            currentStory = newIndex;

                            // Reset progress bars
                            progressBars.forEach((bar, i) => {
                                bar.style.transition = 'none';
                                bar.style.width = i === currentStory ? '0%' : (i < currentStory ? '100%' : '0%');
                                void bar.offsetWidth;
                                if (i === currentStory) {
                                    bar.style.transition = 'width 60s linear';
                                    bar.style.width = '100%';
                                }
                            })
                            storyContent.innerHTML = '';

                            const newContent = storyshow[currentStory].cloneNode(true);
                            newContent.style.width = '100%';
                            newContent.style.height = '100%';
                            newContent.style.objectFit = 'cover';

                            // Reaction + Comment Section
                            const reactionSection = document.createElement('div');
                            const storyId = newContent.id;
                                const viewCounter = document.createElement('div');
                                viewCounter.style.position = 'absolute';
                                viewCounter.style.top = '15px';
                                viewCounter.style.right = '15px';
                                viewCounter.style.background = 'rgba(0,0,0,0.4)';
                                viewCounter.style.color = 'white';
                                viewCounter.style.fontSize = '14px';
                                viewCounter.style.padding = '5px 10px';
                                viewCounter.style.borderRadius = '20px';
                                viewCounter.style.zIndex = '1001';
                                viewCounter.style.cursor='pointer';
                                viewCounter.id="viewCounter";
                                const viewform = new FormData();
                                viewform.append("storyId", storyId);

                                fetch("view_count.php", {
                                    method: 'POST',
                                    body: viewform,
                                    credentials: "same-origin"
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.status === "success") {
                                        viewCounter.innerText = `👁 ${data.view_count} views`;
                                    } else {
                                        console.error(data.message);
                                    }
                                })
                                .catch(error => console.error('Error:', error));
                                storyViewer.appendChild(viewCounter);
                                viewCounter.addEventListener("click", function () {
                                let oldPanel = document.getElementById("viewslot");
                                if (oldPanel) {
                                    oldPanel.remove();
                                    return;
                                }

                                const viewslot = document.createElement("div");
                                viewslot.id = "viewslot";
                                viewslot.style.position = "fixed";
                                viewslot.style.top = "0";
                                viewslot.style.right = "-300px";
                                viewslot.style.width = "300px";
                                viewslot.style.height = "90%";
                                viewslot.style.background = "#fff";
                                viewslot.style.boxShadow = "-2px 0 10px rgba(0,0,0,0.3)";
                                viewslot.style.borderRadius="5px";
                                viewslot.style.padding = "20px";
                                viewslot.style.overflowY = "auto";
                                viewslot.style.zIndex = "2000";
                                viewslot.style.transition = "right 0.4s ease";
                                document.body.appendChild(viewslot);

                                setTimeout(() => { viewslot.style.right = "0"; }, 10);

                                // Close button
                                const closeBtn = document.createElement("button");
                                closeBtn.textContent = "X";
                                closeBtn.style.position = "absolute";
                                closeBtn.style.top = "10px";
                                closeBtn.style.left = "10px";
                                closeBtn.style.background = "#f00";
                                closeBtn.style.color = "#fff";
                                closeBtn.style.border = "none";
                                closeBtn.style.padding = "5px 10px";
                                closeBtn.style.cursor = "pointer";
                                closeBtn.onclick = () => {
                                    viewslot.style.right = "-300px";
                                    setTimeout(() => viewslot.remove(), 400);
                                };
                                viewslot.appendChild(closeBtn);

                                // Header
                                const heading = document.createElement("h3");
                                heading.textContent = "Viewers";
                                heading.style.marginTop = "40px";
                                viewslot.appendChild(heading);
                                console.log("Sending storyId:", storyId);
                                // Viewer data load
                            const viewerForm = new FormData();
                                viewerForm.append("storyId", storyId);
                                // Add this before your fetch call

                                fetch("viewers_list.php", {
                                    method: 'POST',
                                    body: viewerForm,
                                    credentials: "same-origin"
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Network response was not ok');
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    if (data.status === "success" && data.datastory.length > 0) {
                                        data.datastory.forEach(v => {
                                            const div = document.createElement("div");
                                            div.style.display = "flex";
                                            div.style.alignItems = "center";
                                            div.style.borderBottom = "1px solid #ddd";
                                            div.style.padding = "10px 0";
                                            div.innerHTML = `
                                                <img src="${v.profile_picture}" alt="${v.name}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;margin-right:10px;">
                                                <div>
                                                    <h4 style="margin:0; font-size:16px;">${v.name}</h4>
                                                    <p style="margin:3px 0 0;">Reaction: ${v.reaction || 'No reaction'}</p>
                                                    <p style="margin:3px 0 0; color:#555;">Comment: ${v.comment || 'No comment'}</p>
                                                </div>
                                            `;
                                            viewslot.appendChild(div);
                                        });
                                    } else {
                                        const noData = document.createElement("p");
                                        noData.textContent = data.message || "No viewers found.";
                                        viewslot.appendChild(noData);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    const viewslot2 = document.getElementById('viewslot');
                                    viewslot2.innerHTML = `<p style="color:red;">Error loading viewers: ${error.message}</p>`;
                                });
                                });
                            reactionSection.style.padding = '10px';
                            reactionSection.style.width = '100%';
                            reactionSection.style.display = 'flex';
                            reactionSection.style.justifyContent = 'space-around';
                            reactionSection.style.alignItems = 'center';
                            reactionSection.style.gap = '5px';
                            reactionSection.style.position = 'absolute';
                            reactionSection.style.bottom = '20px';
                            reactionSection.style.left = '25%';
                            reactionSection.innerHTML = `
                                <button class="story-reaction" data-reaction="❤️" id="story-${storyId}">❤️</button>
                                <button class="story-reaction" data-reaction="😂" id="story-${storyId}">😂</button>
                                <button class="story-reaction" data-reaction="😮" id="story-${storyId}">😮</button>
                                <button class="story-reaction" data-reaction="😢" id="story-${storyId}">😢</button>
                                <button class="story-reaction" data-reaction="🔥" id="story-${storyId}">🔥</button>
                                <div style="flex:2; display:flex; gap:5px; align-items:center;">
                                    <input type="text" class="story-comment-input" placeholder="Write a comment...">
                                    <button class="story-comment-send" id="comment-${storyId}">Send</button>
                                </div>
                            `;

                            newContent.appendChild(reactionSection);
                            storyContent.appendChild(newContent);

                            // Reaction event
                            reactionSection.querySelectorAll('.story-reaction').forEach(btn => {
                                btn.addEventListener('click', (e) => {
                                    const reaction = e.target.getAttribute('data-reaction');
                                    const floatingReaction = document.createElement('div');
                                    floatingReaction.textContent = reaction;
                                    floatingReaction.style.position = 'absolute';
                                    floatingReaction.style.top = '50%';
                                    floatingReaction.style.left = '50%';
                                    floatingReaction.style.transform = 'translate(-50%, -50%)';
                                    floatingReaction.style.fontSize = '60px';
                                    floatingReaction.style.opacity = '0';
                                    floatingReaction.style.animation = 'floatReaction 1.5s forwards';
                                    storyContent.appendChild(floatingReaction);
                                    setTimeout(() => storyContent.removeChild(floatingReaction), 1500);
                                    console.log(`Reaction on story ${storyId}:`, reaction);
                                    const form=new FormData();
                                    form.append("story_id",storyId);
                                    form.append("reaction",reaction);
                                    fetch('save_reaction.php', {
                                        method: 'POST',
                                        body:form,
                                        credentials:"same-origin"
                                        
                                        })
                                        .then(response => response.json())
                                        .then(data => {
                                        })
                                        .catch(error => console.error('Error:', error));
                                    });
                                });
                            
                            // Comment event
                            const commentInput = reactionSection.querySelector('.story-comment-input');
                            const commentSend = reactionSection.querySelector('.story-comment-send');

                            commentSend.addEventListener('click', () => {
                                const comment = commentInput.value.trim();
                                if (comment) {
                                    const form = new FormData();
                                    form.append("story_id", storyId);
                                    form.append("comment", comment);
                                    fetch("story_comment.php", {
                                        method: "POST",
                                        body: form,
                                        credentials: "same-origin"
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        console.log('Comment saved:', data);
                                        if (data.status === "success") {
                                            alert("Comment sent!");
                                            commentInput.value = "";
                                        } else {
                                            alert("Error: " + data.message);
                                        }
                                    })
                                    .catch(error => console.error('Error:', error));
                                }


                            });

                            // Play videos
                            const videos = newContent.querySelectorAll('video');
                            videos.forEach(video => {
                                video.muted = false;
                                video.autoplay = true;
                                video.loop = false;
                                video.play().catch(e => console.log("Autoplay prevented:", e));
                                video.onended = () => {
                                    progressBars[currentStory].style.width = '100%';
                                };
                            });
                            const form=new FormData();
                            form.append('post_id',storyId);
                            form.append("post_type","story");
                            fetch("view.php",{
                                method: "POST",
                                        body: form,
                                        credentials: "same-origin"
                            }) .then(response => response.json())
                                    .then(data => {
                                        console.log('view saved:', data);
                                        if (data.status === "success") {
                                            commentInput.value = "";
                                        } else {
                                            alert("Error: " + data.message);
                                        }
                                    })
                                    .catch(error => console.error('Error:', error));
                        }
                        document.body.appendChild(storyViewer);
                        progressBars[currentStory].style.transition = 'width 60s linear';
                        progressBars[currentStory].style.width = '100%';
                        loadStory(currentStory);
                        function startNewInterval() {
                            clearInterval(progressInterval);
                            progressInterval = setInterval(() => {
                                if (currentStory < storyshow.length - 1) {
                                    loadStory(currentStory + 1);
                                } else {
                                    clearInterval(progressInterval);
                                    document.body.removeChild(storyViewer);
                                }
                                viewslot.style.right = "-300px";
                                    setTimeout(() => viewslot.remove(), 400);
                            }, 30000);
                            
                        }
                        startNewInterval();
                    storyViewer.addEventListener('touchstart', (e) => {
                            touchStartX = e.changedTouches[0].screenX;
                        }, { passive: true });

                        storyViewer.addEventListener('touchend', (e) => {
                            touchEndX = e.changedTouches[0].screenX;
                            handleSwipe();
                        }, { passive: true });

                        function handleSwipe() {
                            const threshold = 50;
                            if (touchEndX < touchStartX - threshold) {
                                if (currentStory < storyshow.length - 1) {
                                    clearInterval(progressInterval);
                                    loadStory(currentStory + 1);
                                    startNewInterval();
                                } else {
                                    document.body.removeChild(storyViewer);
                                }
                            } else if (touchEndX > touchStartX + threshold) {
                                if (currentStory > 0) {
                                    clearInterval(progressInterval);
                                    loadStory(currentStory - 1);
                                    startNewInterval();
                                }
                            }
                        }
                        storyViewer.addEventListener('click', (e) => {
                            const clickX = e.clientX;
                            const viewerWidth = storyViewer.offsetWidth;
                            if (clickX < viewerWidth * 0.3) {
                                if (currentStory > 0) {
                                    clearInterval(progressInterval);
                                    loadStory(currentStory - 1);
                                    startNewInterval();
                                }
                            } else if (clickX > viewerWidth * 0.7) {
                                if (currentStory < storyshow.length - 1) {
                                    clearInterval(progressInterval);
                                    loadStory(currentStory + 1);
                                    startNewInterval();
                                } else {
                                    document.body.removeChild(storyViewer);
                                }
                            }
                        });

                        // Floating reaction animation
                        const style = document.createElement('style');
                        style.textContent = `
                            @keyframes floatReaction {
                                0% { transform: translate(-50%, -50%) scale(0.5); opacity: 0; }
                                20% { transform: translate(-50%, -80%) scale(1.2); opacity: 1; }
                                100% { transform: translate(-50%, -180%) scale(0.8); opacity: 0; }
                            }
                        `;
                        document.head.appendChild(style);
                    });
                }); // Access the html property
            })
            .catch(error => {
                console.error('Error:', error);
            });
        
      // Set button dimensions
            storyButton.style.marginLeft="20px";
            storyButton.style.width = '200px';
            storyButton.style.height = '280px';
            storyButton.style.position = 'relative';
            storyButton.style.display = 'flex';
            storyButton.style.alignItems = 'center';
            storyButton.style.justifyContent = 'center';
            storyButton.style.border="2px solid black";
            storyButton.style.boxShadow="1px 1px 1px 1px  black";
            storyButton.style.marginTop="10px";
            
            // Resize profile image inside button
            const profileImg = storyButton.querySelector('.story img');
            if (profileImg) {
                profileImg.style.marginLeft="40px";
                profileImg.style.width = '170px';
                profileImg.style.height = '270px';
                profileImg.style.objectFit = 'cover';
            }
            
            // Resize plus icon
            const plusIcon = storyButton.querySelector('.creat img');
            if (plusIcon) {
                plusIcon.style.width = '40px';
                plusIcon.style.height = '40px';
                plusIcon.style.position = 'absolute';
                plusIcon.style.bottom = '5px';
                plusIcon.style.right = '10px';
                plusIcon.style.backgroundColor = '#1877f2';
                plusIcon.style.borderRadius = '50%';
                plusIcon.style.padding = '5px';
                plusIcon.style.border = '3px solid white';
            }
        }
    });
    
    const postElement = document.querySelector(".post");
    postElement.style.cssText = `
        height: 300px;
        margin-top:20px;
        display: flex;
        overflow-x: auto;
        gap: 40px;
        padding: 10px;
    `;
 
    ///story creator;
    const story = document.querySelector("#story");

    story.addEventListener("click", function() {
        // Clear and prepare the body
        document.body.innerHTML = '';
        document.body.style.cssText = `
            height: 100vh;
            margin: 0;
            background-color: #f0f2f5;
            font-family: Arial, sans-serif;
        `;

        // Main container
        const container = document.createElement("div");
        container.style.cssText = `
            width: 400px;
            margin-left: 35%;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        `;
            document.body.appendChild(nav);
        // Story preview area
        const preview = document.createElement("div");
        preview.style.cssText = `
            height: 450px;
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            position: relative;
            overflow: hidden;
            margin-bottom: 15px;
        `;

        // Profile section
        const profile = document.createElement("div");
        profile.style.cssText = `
            display: flex;
            align-items: center;
            padding: 10px;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 2;
        `;

        const profileImg = document.createElement("img");
        profileImg.src = profileImageUrl;
        profileImg.style.cssText = `
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ff0000;
            margin-right: 10px;
        `;

        const profileName = document.createElement("span");
        profileName.textContent = name;
        profileName.style.cssText = `
            color: white;
            font-weight: bold;
            text-shadow: 0 1px 3px rgba(0,0,0,0.3);
        `;

        // Media elements
        const mediaPreview = document.createElement("div");
        mediaPreview.style.cssText = `
            width: 100%;
            height: 100%;
            position: relative;
        `;

        // File inputs
        const mediaInput = document.createElement("input");
        mediaInput.type = "file";
        mediaInput.accept = "image/*,video/*";
        mediaInput.style.display = "none";
        mediaInput.name = "media";

        const audioInput = document.createElement("input");
        audioInput.type = "file";
        audioInput.accept = "audio/*";
        audioInput.style.display = "none";
        audioInput.name = "audio";

        // Action buttons
        const addMediaBtn = createActionButton("📷 Add Media", "left");
        const addAudioBtn = createActionButton("🎵 Add Audio", "right");

        // Caption input
        const caption = document.createElement("textarea");
        caption.placeholder = "Write your story caption...";
        caption.name = "text_content";
        caption.style.cssText = `
            width: 80%;
            height: 80px;
            padding: 10px;
            margin-top: 10px;
            margin-left: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: none;
        `;

        // Submit button
        const submitBtn = document.createElement("button");
        submitBtn.textContent = "Post Story";
        submitBtn.style.cssText = `
            width: 100%;
            padding: 12px;
            margin-top: 15px;
            background: #1877f2;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        `;

        // Helper function for action buttons
        function createActionButton(text, position) {
            const btn = document.createElement("div");
            btn.textContent = text;
            btn.style.cssText = `
                position: absolute;
                bottom: 60px;
                ${position}: 10px;
                padding: 8px 12px;
                background: rgba(0, 0, 0, 0.7);
                color: white;
                border-radius: 20px;
                cursor: pointer;
                z-index: 2;
            `;
            return btn;
        }

        // Event listeners
        addMediaBtn.addEventListener("click", () => mediaInput.click());
        addAudioBtn.addEventListener("click", () => audioInput.click());

        mediaInput.addEventListener("change", handleMediaUpload);
        audioInput.addEventListener("change", handleAudioUpload);
        submitBtn.addEventListener("click", handleSubmit);

        function handleMediaUpload() {
            const file = mediaInput.files[0];
            if (!file) return;

            mediaPreview.innerHTML = '';
            
            const reader = new FileReader();
            reader.onload = function(e) {
                if (file.type.startsWith("image/")) {
                    mediaPreview.style.backgroundImage = `url('${e.target.result}')`;
                    mediaPreview.style.backgroundSize = "cover";
                    mediaPreview.style.backgroundPosition = "center";
                } else if (file.type.startsWith("video/")) {
                    const video = document.createElement("video");
                    video.src = e.target.result;
                    video.muted=false;
                    video.autoplay = true;
                    video.loop = true;
                    video.style.cssText = `
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                    `;
                    mediaPreview.appendChild(video);
                }
            };
            reader.readAsDataURL(file);
        }

        function handleAudioUpload() {
            const file = audioInput.files[0];
            if (!file) return;
            console.log("Audio file selected:", file.name);
        }

        async function handleSubmit() {
        const hasMedia = mediaInput.files.length > 0;
        const hasAudio = audioInput.files.length > 0;
        const hasText = caption.value.trim() !== '';
        
        if (!hasMedia && !hasAudio && !hasText) {
            alert('Please add at least one content type (media, audio, or text)');
            return;
        }

        const formData = new FormData();
        
        // Add content only if exists
        if (hasText) formData.append("text_content", caption.value.trim());
        if (hasMedia) formData.append("media", mediaInput.files[0]);
        if (hasAudio) formData.append("audio", audioInput.files[0]);

        // UI feedback
        submitBtn.disabled = true;
        submitBtn.textContent = "Posting...";

        try {
            const response = await fetch("upload_story.php", {
                method: "POST",
                body: formData
            });

            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || "Failed to post story");
            }
            
        } catch (error) {
            console.error("Error:", error);
            alert(`Error: ${error.message}`);
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = "Post Story";
        }
        fetch("story.php", { method: "POST", credentials: "same-origin" })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }
             const stories = {
                image: data.image,
                video: data.video,
                audio: data.audio,
                text: data.text,
                user_id:data.user_id,
                story_id:data.story_id
            };
            console.log(stories);
            if (stories.image || stories.video || stories.text) {
                const storyEl = createStoryElement(
                    stories.image || stories.video || stories.text,
                    stories.image ? 'image' : (stories.video ? 'video' : 'text'),stories.text,stories.user_id,stories.story_id
                );
            } else {
                console.log("No story content found");
            }

        })
        .catch(error => console.error("Error fetching story:", error));
         function createStoryElement(content, type,text,user_id,story_id) {
                const storyEl = document.createElement("div");
                storyEl.id="post_"+ user_id + "_"+ story_id;
                storyEl.className="storyel";
                storyEl.style.cssText = `
                    height: 280px;
                    width: 200px;
                    flex-shrink: 0;
                    margin-top:10px;
                    margin-left:10px;
                    border-radius: 10px;
                    position: relative;
                    overflow: hidden;
                    cursor: pointer;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                `;

                // Create content based on type
                if (type === 'image' && content) {
                    const img = document.createElement('img');
                    img.src= content;
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';
                    if(text) {
                    const textEl = document.createElement('div');
                    textEl.textContent = text ;
                    textEl.style.color = 'white';
                    textEl.style.padding = '15px';
                    textEl.style.textAlign = 'center';
                    img.appendChild(textEl);
                    }
                    storyEl.appendChild(img);
                } 
                else if (type === 'video' && content) {
                    const video = document.createElement('video');
                    video.src = content;
                    video.autoplay = true;
                    video.muted=true;
                    video.loop = true;
                    video.style.width = '100%';
                    video.style.height = '100%';
                    video.style.objectFit = 'cover';
                    video.textContent=text;
                    storyEl.appendChild(video);
                    if(text) {
                    const textEl = document.createElement('div');
                    textEl.textContent = text ;
                    textEl.style.color = 'white';
                    textEl.style.padding = '15px';
                    textEl.style.textAlign = 'center';
                    storyEl.appendChild(textEl);
                }
                }
                else if(type==="text" && content) {
                    // Default text story
                    storyEl.style.backgroundColor = '#1877f2';
                    storyEl.style.display = 'flex';
                    storyEl.style.alignItems = 'center';
                    storyEl.style.justifyContent = 'center';
                    
                    const textEl = document.createElement('div');
                    textEl.textContent = content ;
                    textEl.style.color = 'white';
                    textEl.style.padding = '15px';
                    textEl.style.textAlign = 'center';
                    storyEl.appendChild(textEl);
                }

                // Add profile at bottom
                const profile = document.createElement("div");
                profile.style.cssText = `
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    padding: 10px;
                    background: linear-gradient(transparent, rgba(0,0,0,0.7));
                    display: flex;
                    align-items: center;
                `;

                const profileImg = document.createElement("img");
                profileImg.src = profileImageUrl;
                profileImg.style.cssText = `
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    border: 2px solid #1877f2;
                    object-fit: cover;
                `;

                const profileName = document.createElement("div");
                profileName.textContent = name;
                profileName.style.cssText = `
                    color: white;
                    margin-left: 10px;
                    font-size: 0.8rem;
                    font-weight: bold;
                `;

                profile.appendChild(profileImg);
                profile.appendChild(profileName);
                storyEl.appendChild(profile);
                const creatorid="post_"+ user_id + "_"+ story_id;
                const formdata2 = new FormData();
                formdata2.append("post12", storyEl.outerHTML);
                formdata2.append("post_type", "story");
                formdata2.append("creator", creatorid);
                formdata2.append("caption",caption.value.trim() );
                fetch("post.php", {
                    method: "POST",
                    body: formdata2,
                    credentials: "same-origin"
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert("Post saved successfully!");
                    } else {
                        alert("Failed: " + data.error);
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("An error occurred");
                });
                
            }
           
    }
        profile.appendChild(profileImg);
        profile.appendChild(profileName);
        
        preview.appendChild(profile);
        preview.appendChild(addMediaBtn);
        preview.appendChild(addAudioBtn);
        preview.appendChild(mediaPreview);
        
        container.appendChild(preview);
        container.appendChild(caption);
        container.appendChild(submitBtn);
        
        document.body.appendChild(container);
        

        // Append hidden inputs
        container.appendChild(mediaInput);
        container.appendChild(audioInput);
        

    });

    const post_box=document.querySelector(".post-box");
    post_box.addEventListener("click", function () {
    document.body.innerHTML = "";
    document.body.style.display = "flex";
    document.body.style.justifyContent = "center";
    document.body.style.alignItems = "center";
    document.body.style.height = "100vh";
    document.body.style.margin = "0";
    document.body.style.backgroundColor = "#f0f2f5";

    // Main container
    let selectContainer = document.createElement("div");
    selectContainer.style.width = "600px";
    selectContainer.style.maxHeight = "90vh";
    selectContainer.style.overflowY = "auto";
    selectContainer.style.backgroundColor = "white";
    selectContainer.style.border = "1px solid #ccc";
    selectContainer.style.borderRadius = "10px";
    selectContainer.style.boxShadow = "0 2px 8px rgba(0, 0, 0, 0.1)";
    selectContainer.style.padding = "20px";
    selectContainer.style.display = "flex";
    selectContainer.style.flexDirection = "column";
    selectContainer.style.gap = "15px";
    selectContainer.style.fontFamily = "Arial, sans-serif";

    // Profile + name row
    let profileRow = document.createElement("div");
    profileRow.style.display = "flex";
    profileRow.style.alignItems = "center";
    profileRow.style.gap = "10px";

    let profilePic = document.createElement("img");
    profilePic.src = profileImageUrl;
    profilePic.alt = "Profile";
    profilePic.style.width = "50px";
    profilePic.style.height = "50px";
    profilePic.style.borderRadius = "50%";
    profilePic.style.objectFit = "cover";
    profilePic.style.boxShadow="2px black";

    let userName = document.createElement("div");
    userName.textContent = <?php echo json_encode($user_name); ?>;
    userName.style.fontWeight = "bold";
    userName.style.fontSize = "1rem";

    profileRow.appendChild(profilePic);
    profileRow.appendChild(userName);

    // Caption input
    let captionInput = document.createElement("textarea");
    captionInput.id = "caption";  // set ID for consistency
    captionInput.placeholder = "Write your caption...";
    captionInput.style.width = "100%";
    captionInput.style.minHeight = "80px";
    captionInput.style.padding = "10px";
    captionInput.style.fontSize = "1rem";
    captionInput.style.border = "1px solid #ccc";
    captionInput.style.borderRadius = "8px";
    captionInput.style.resize = "vertical";

    // File input
    let fileInput = document.createElement("input");
    fileInput.type = "file";
    fileInput.accept = "image/*,video/*";
    fileInput.multiple = true;
    fileInput.id = "mediaInput"; // set ID for consistency
    fileInput.style.width = "100%";
    fileInput.style.marginTop = "10px";

    // Preview container
    let previewContainer = document.createElement("div");
    previewContainer.style.display = "flex";
    previewContainer.style.flexDirection = "column";
    previewContainer.style.alignItems = "center";
    previewContainer.style.marginTop = "10px";
    previewContainer.style.gap = "10px";

    // Preview on file select
    fileInput.addEventListener("change", () => {
        previewContainer.innerHTML = "";

        Array.from(fileInput.files).forEach(file => {
            let fileURL = URL.createObjectURL(file);

            if (file.type.startsWith("image/")) {
                let img = document.createElement("img");
                img.src = fileURL;
                img.style.maxWidth = "100%";
                img.style.height = "auto";
                img.style.borderRadius = "8px";
                img.style.border = "1px solid #999";
                previewContainer.appendChild(img);
            } else if (file.type.startsWith("video/")) {
                let video = document.createElement("video");
                video.id="video";
                video.src = fileURL;
                video.controls = true;
                video.style.width = "100%";
                video.style.maxHeight = "300px";
                video.style.borderRadius = "8px";
                video.style.border = "1px solid #999";
                previewContainer.appendChild(video);
            }
        });
    });

    // Post button
    let postBtn = document.createElement("button");
    postBtn.id = "postButton";
    postBtn.textContent = "Post";
    postBtn.style.marginTop = "10px";
    postBtn.style.padding = "10px";
    postBtn.style.fontSize = "1rem";
    postBtn.style.backgroundColor = "#ff0000";
    postBtn.style.color = "white";
    postBtn.style.border = "none";
    postBtn.style.borderRadius = "6px";
    postBtn.style.cursor = "pointer";

    // Post button click event: Upload media & post content
    postBtn.addEventListener("click", () => {
            const caption = captionInput.value.trim();
            const files = fileInput.files;

            if (!caption && files.length === 0) {
                alert("Please write something or select an image/video.");
                return;
            }

            const formData = new FormData();
            formData.append("post12", caption);
            for (const file of files) {
                formData.append("media_files[]", file);
            }

            fetch("upload_post_media.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(response => {
            const resultArray = response.results || [];
            const errors = resultArray.filter(r => !r.success);
            const successCount = resultArray.filter(r => r.success).length;

            if (successCount > 0) {
                alert(successCount + " file(s) uploaded successfully.");
                captionInput.value = "";
                fileInput.value = "";
                previewContainer.innerHTML = "";
                fetch('media_api.php', {
                    method: 'POST', // could also be GET, doesn't matter since no user_id sent
                    headers: { 'Content-Type': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        console.error(data.error);
                        return;
                    }
                    if (data.message) {
                        console.log(data.message);
                    }
                    const creatorid="post_"+ <?php echo json_encode($user_id);?> + "_"+ data.id;
                    // Build post (same as before)
                    const post1 = document.createElement("div");
                    post1.className = "post_"+ <?php echo json_encode($user_id);?> + "_"+ data.id;
                    post1.style.cssText = `
                        height: auto;
                        border: 2px solid black;
                        display: block;
                        box-shadow: 2px 2px 2px 2px black;
                        border-radius: 15px;
                        margin-bottom: 20px;
                    `;

                    const creatpostdiv = document.createElement("div");
                    creatpostdiv.style.cssText = `
                        width: 100%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        background: gray;
                    `;

                    const postprofile = document.createElement("div");
                    postprofile.style.cssText = `
                        width: 100%;
                        border-bottom: 2px solid black;
                        box-shadow: 2px 2px 2px solid black;
                        border-top-left-radius: 10px;
                        border-top-right-radius: 10px;
                        background: #f9f9f9;
                        display: flex;
                    `;
                    let posttype="";
                    let postimg;
                    if (data.type === "image") {
                        postimg = document.createElement("img");
                        postimg.src = data.media_url;
                        posttype="image";
                    } else if (data.type === "video") {
                        postimg = document.createElement("video");
                        postimg.src = data.media_url;
                        postimg.controls = true;
                        posttype="video";
                        postimg.className="video";
                        postimg.id=creatorid;
                    }else{
                        posttype="text";
                    }
                    if (postimg) {
                        postimg.style.height = "600px";
                        postimg.style.width = "600px";
                        postimg.style.objectFit = "cover";
                    }

                    const name = document.createElement("div");
                    name.id="name"+creatorid;
                    name.innerText =<?php echo json_encode($user_name); ?> ;
                    name.style.cssText = `
                        font-size: 0.9rem;
                        font-weight: bold;
                        margin-left: 5px;
                    `;

                    const profilepic = document.createElement("div");
                    profilepic.style.cssText = `
                        height: 50px;
                        width: 50px;
                        border-radius: 50%;
                        box-shadow: 0 0 10px rgba(0,0,0,0.1);
                    `;

                    const profileimg = document.createElement("img");
                    profileimg.id="profile_img"+creatorid;
                    profileimg.src =profileImageUrl;
                    profileimg.style.cssText = `
                        object-fit: cover;
                        height: 50px;
                        width: 50px;
                        border-radius: 50%;
                    `;
                    profilepic.appendChild(profileimg);

                    postprofile.appendChild(profilepic);
                    postprofile.appendChild(name);
                    post1.appendChild(postprofile);

                    const captionText = document.createElement("p");
                    captionText.id="caption"+creatorid;
                    captionText.innerText = data.caption;
                    captionText.style.cssText = `
                        white-space: pre-wrap;
                        overflow-wrap: break-word;
                        width: 100%;
                        box-sizing: border-box;
                        display: block;
                        padding: 10px;
                        font-size: 1rem;
                        font-weight: normal;
                        border-radius: 5px;
                        background: #d4cacaff;
                    `;
                    post1.appendChild(captionText);

                    creatpostdiv.appendChild(postimg);
                    post1.appendChild(creatpostdiv);
                    const likecomentshare = document.createElement("div");
                likecomentshare.style.height = "50px";
                likecomentshare.style.display = "flex";
                likecomentshare.style.justifyContent = "space-around";
                likecomentshare.style.alignItems = "center";
                likecomentshare.style.borderTop = "2px solid black";
                likecomentshare.style.boxShadow="2px solid black";
                likecomentshare.style.backgroundColor = "#f9f9f9";
                const seelike=document.createElement("div");
                seelike.innerText="see";
                seelike.className="seelike";
                seelike.id="post_"+ <?php echo json_encode($user_id);?> + "_"+ data.id
                seelike.style.height="25px";
                seelike.style.width="30px"
                seelike.style.border="1px solid black";
                seelike.style.borderRadius="50%";
                seelike.style.boxShadow="1px 1px 1px black";
                seelike.style.marginLeft="8%";
                seelike.style.marginRight="2%";
                likecomentshare.appendChild(seelike);
                // Like Button
                const like = document.createElement("div");
                like.className="Like"; 
                like.innerText = '👍 Like';
                like.style.display = "flex";
                like.style.justifyContent = "center";
                like.style.alignItems = "center";
                like.style.cursor = "pointer";
                like.style.marginRight="20%";
                like.id="post_"+ <?php echo json_encode($user_id);?> + "_"+ data.id
                

                // Comment Button
                const COMENT = document.createElement("div");
                COMENT.className="Comment";
                COMENT.innerText = '💬 Comment';
                COMENT.id="post_"+ <?php echo json_encode($user_id);?> + "_"+ data.id
                COMENT.style.display = "flex";
                COMENT.style.justifyContent = "center";
                COMENT.style.alignItems = "center";
                COMENT.style.cursor = "pointer";
                // Share Button
                const SHARE = document.createElement("div");
                SHARE.className="Share";
                SHARE.id="post_"+ <?php echo json_encode($user_id);?> + "_"+ data.id
                SHARE.innerText = '↗️ Share';
                SHARE.style.display = "flex";
                SHARE.style.justifyContent = "center";
                SHARE.style.alignItems = "center";
                SHARE.style.cursor = "pointer";
                SHARE.style.marginLeft="20%";
                const seeSHARE=document.createElement("div");
                seeSHARE.innerText="view";
                seeSHARE.className="seeSHARE";
                seeSHARE.id="post_"+ <?php echo json_encode($user_id);?> + "_"+ data.id
                seeSHARE.style.height="25px";
                seeSHARE.style.width="30px";
                seeSHARE.style.border="1px solid black";
                seeSHARE.style.borderRadius="50%";
                seeSHARE.style.boxShadow="1px 1px 1px black";
                seeSHARE.style.marginLeft="2%";
                seeSHARE.style.marginRight="2%";
                // Append buttons to container
                likecomentshare.appendChild(like);
                likecomentshare.appendChild(COMENT);
                likecomentshare.appendChild(SHARE);
                likecomentshare.appendChild(seeSHARE);
                post1.appendChild(likecomentshare);

                const formdata1 = new FormData();
                formdata1.append("post12", post1.outerHTML);
                formdata1.append("post_type", posttype);
                formdata1.append("creator", creatorid);
                 formdata1.append("caption", data.caption);
                fetch("post.php", {
                    method: "POST",
                    body: formdata1,
                    credentials: "same-origin"
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert("Post saved successfully!");
                    } else {
                        alert("Failed: " + data.error);
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("An error occurred");
                });
                    document.body.appendChild(post1);
                })
                .catch(err => console.error("Error api:", err));
            }

            if (errors.length > 0) {
                errors.forEach(err => {
                    console.error("Upload error:", err.error);
                });
                alert("Some files failed to upload. Check console for details.");
            }
        })
        .catch(error => {
            console.error("Upload failed", error);
            alert("Upload failed.");
        }); 

     });


            // Append elements to container
            selectContainer.appendChild(profileRow);
            selectContainer.appendChild(captionInput);
            selectContainer.appendChild(fileInput);
            selectContainer.appendChild(previewContainer);
            selectContainer.appendChild(postBtn);

            // Append container to body
            document.body.appendChild(selectContainer);
        });
profile.addEventListener("click", function () {
    const user_id=<?=json_encode($user_id);?> ;
    const form=new FormData();
    form.append("user_id",user_id )
    fetch("fetch_content.php",{
        method:"POST",
        body:form,
        credentials:"same-origin"
    }).then(response=>response.json())
    .then(data=>{
        const profileimgurl=<?=json_encode($profile_image_url); ?>;// Post create kora
        const id= <?=json_encode($id);?>;
        const username=<?= json_encode($user_name); ?>;
        const htmldata=data.posts;
        profileclick(profileimgurl ,user_id,id,username,htmldata,fixedid);

    }).catch(err=>{
        console.error(err);
    })
    
        
}); 
function profileclick(profileimgurl1 ,user_id1,id1,username1,htmldata1,fixed){
            const profileimgurl=profileimgurl1;// Post create kora
            const post1 = document.createElement("div");
            const user_id=user_id1;
            const id= id1;
            const username=username1;
            const htmldata=htmldata1;
            const fixedid=fixed;
            document.body.innerHTML = "";
            document.body.style.display = "flex";
            document.body.style.justifyContent = "center";
            document.body.style.alignItems = "center";
            document.body.style.height = "1000vh";
            document.body.style.margin = "0";
            document.body.style.backgroundColor = "white";

            let selectContainer = document.createElement("div");
            selectContainer.style.height = "100%";
            selectContainer.style.width = "100%";
            document.body.style.display = "flex";
            document.body.style.justifyContent = "center";
            document.body.style.alignItems = "center";
            selectContainer.style.background = "rgba(201, 200, 200, 1)";
            selectContainer.style.borderRadius = "10px";
            selectContainer.style.boxShadow = "0 0 10px rgba(0,0,0,0.1)";
            document.body.appendChild(selectContainer);

            const cover = document.createElement("div");
            cover.style.height = "350px";
            cover.style.width = "700px";
            cover.style.display="flex";
            cover.style.alignItems="center";
            cover.style.justifyContent="center";
            cover.style.backgroundSize = "cover";
            cover.style.marginTop = "2%";
            cover.style.marginLeft = "17%";
            cover.style.boxShadow = "0 0 8px rgba(0, 0, 0, 1)";
            cover.style.borderRadius="10px"
            
            const img = document.createElement("img");
            img.src =profileimgurl;
            img.style.height = "350px";
            img.style.width = "700px";
            img.style.objectFit = "contain";
            cover.appendChild(img);

            const ppic = document.createElement("div");
            const ppdiv = document.createElement("div");
            const button = document.createElement("button");
            button.style.borderRadius = "50%";
            button.style.boxShadow = "0 0 8px rgba(0, 0, 0, 1)";


            ppdiv.style.height = "150px";
            ppdiv.style.width = "700px";
            ppdiv.style.backgroundSize = "cover";
            ppdiv.style.marginTop = "1%";
            ppdiv.style.borderRadius="10px";
            ppdiv.style.marginLeft = "17%";
            ppdiv.style.display = "flex";
            ppdiv.style.boxShadow = "0 0 8px rgba(0, 0, 0, 1)";
            ppic.style.height = "150px";
            

            const img1 = document.createElement("img");
            img1.src = profileimgurl;
            img1.style.height = "150px";
            img1.style.width = "150px";
            img1.style.objectFit = "cover";
            img1.style.borderRadius = "50%";
            ppic.style.width = "150px";
            ppic.style.display = "flex";
            ppic.style.marginLeft = "2%";
            ppic.style.justifyContent = "center";
            ppic.style.alignItems = "center";
            let  selectedFile=null;
            if(fixedid===user_id){
            button.addEventListener("click", function () {
            const mediainput = document.createElement("input");
            mediainput.type = "file";
            mediainput.accept = "image/*";
            mediainput.style.display = "none";
            document.body.appendChild(mediainput);
            mediainput.click();
            mediainput.addEventListener("change", function () {
                if (mediainput.files[0]) {
                    selectedFile = mediainput.files[0];
                    img1.src = URL.createObjectURL(selectedFile);
                }
                document.body.removeChild(mediainput);
            });
        });}

    button.appendChild(img1);
    ppic.appendChild(button);
    if(fixedid===user_id){
     ppic.addEventListener("click", function () {
        document.body.innerHTML = "";
        document.body.style.display = "flex";
        document.body.style.justifyContent = "center";
        document.body.style.alignItems = "center";
        document.body.style.height = "100vh";
        document.body.style.margin = "0";
        document.body.style.backgroundColor = "white";

        let selectContainer = document.createElement("div");
        selectContainer.style.width = "100%";
        document.body.style.display = "flex";
        document.body.style.justifyContent = "center";
        document.body.style.alignItems = "center";
        selectContainer.style.background = "rgb(226, 226, 226)";
        selectContainer.style.borderRadius = "10px";
        selectContainer.style.boxShadow = "0 0 10px rgba(0,0,0,0.1)";
        selectContainer.appendChild(nav);
        document.body.appendChild(selectContainer);

        const caption = document.createElement("textarea");
        caption.type = "text";
        caption.placeholder = "Write a caption...";
        caption.style.width = "90%";
        caption.style.padding = "10px";
        caption.style.marginLeft = "20px";
        caption.style.display = "block";
        caption.style.fontSize = "1rem";
        caption.style.border = "1px solid #ccc";
        caption.style.borderRadius = "5px";
        caption.style.boxShadow = "0 0 5px rgba(0,0,0,0.1)";

    
        const cover = document.createElement("div");
        cover.style.height = "450px";
        cover.style.width = "700px";
        cover.style.overflow = "hidden";
        cover.style.background = "white";
        cover.style.borderRadius = "5px";
        cover.style.boxShadow = "0 0 10px rgba(255, 0, 0, 0.35)";
        cover.style.display = "block";
        cover.style.justifyContent = "center";
        cover.style.alignItems = "center";
        cover.style.marginLeft = "17%";

        const profileDiv = document.createElement("div");
        const postButton = document.createElement("button");
        postButton.style.height = "50px";
        postButton.style.width = "70px";
        postButton.style.background = "#ff0000";
        postButton.innerText = "Post";
        postButton.style.fontSize = "1rem";
        postButton.style.fontWeight = "bold";
        postButton.style.color = "white";
        postButton.style.marginLeft= "90%";
        
        if(fixedid===user_id){
        postButton.addEventListener("mouseover", function () {
            postButton.style.background = "gray";
            console.log(fixedid);
        });
        postButton.addEventListener("mouseout", function () {
            postButton.style.background = "#ff0000";
        });

        postButton.addEventListener("click", function () {
            if (!selectedFile) {
                alert("Please select a file first!");
                return;
            }

            const originalSrc = img1.src;
            img1.src = profileImageUrl;

            const formdata = new FormData();
            formdata.append("profile_image", selectedFile);

            fetch("upload.php", {
                method: "POST",
                body: formdata,
                credentials: 'same-origin'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    img1.src = data.imageUrl;
                    takeimgurl = data.imageUrl;
                    alert(data.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    throw new Error(data.error || "Upload failed");
                }
            })
            .catch(err => {
                console.error("Upload error:", err);
                img1.src = originalSrc;
                alert("Error: " + (err.message || "Upload failed"));
            });
            
            post1.className = "post";
            post1.style.height="auto";
            post1.style.border = "2px solid black";
            post1.style.display="block";
            const creatpostdiv=document.createElement("div");
            creatpostdiv.style.width="100%";
            creatpostdiv.style.display="flex";
            creatpostdiv.style.alignItems="center";
            creatpostdiv.style.justifyContent="center";
            creatpostdiv.style.background="gray";
            const postprofile=document.createElement("div");
            postprofile.style.width="100%";
            postprofile.style.display="flex";
            const postimg=document.createElement("img");
            postimg.src=profileimgurl;
            postimg.style.height="600px";
            postimg.style.width="600px";
            postimg.style.objectFit="contain";
            const name=document.createElement("div");
            name.innerText=username;
            name.style.fontSize="0.9rem";
            name.style.marginLeft="5px";
            
            const profilepic=document.createElement("div");
            profilepic.style.height="50px";
            profilepic.style.width="50px";
            profilepic.style.borderRadius="50%";
            profilepic.style.border="1px solid #ff0000";
            const profileimg=document.createElement("img");
            profileimg.style.objectFit="contain";
            profileimg.style.height="50px";
            profileimg.style.width="50px";
            profileimg.style.borderRadius="50%";
            profileimg.src= profileimgurl;
            //Optional content: post1.innerHTML = "Your content";
            profilepic.appendChild(profileimg);
            postprofile.appendChild(profilepic);
            postprofile.appendChild(name);
            post1.appendChild(postprofile);
            const captionText = document.createElement("p");
            captionText.innerText = caption.value;
            captionText.style.whiteSpace = "pre-wrap"; // keeps \n line breaks
            captionText.style.overflowWrap = "break-word"; // wraps long words
            captionText.style.width = "100%"; // ensures it uses full width of container\
            captionText.style.boxSizing = "border-box"; // include padding in width
            captionText.style.display = "block"; // ensure it’s not inline
            captionText.style.padding = "10px";
            captionText.style.fontSize = "1rem";
            captionText.style.fontWeight = "normal";
            captionText.style.borderRadius="5px";
            
            post1.appendChild(captionText);
            creatpostdiv.appendChild(postimg);
            post1.appendChild(creatpostdiv);
            const likecomentshare = document.createElement("div");
            likecomentshare.style.height = "50px";
            likecomentshare.style.display = "flex";
            likecomentshare.style.justifyContent = "space-around";
            likecomentshare.style.alignItems = "center";
            likecomentshare.style.borderTop = "1px solid #ccc";
            likecomentshare.style.backgroundColor = "#f9f9f9";
            likecomentshare.style.userSelect = "none";
            const seelike=document.createElement("div");
            seelike.innerText="see";
            seelike.style.height="100";
            function createButton(text, emoji) {
                const btn = document.createElement("div");
                btn.className=text;
                btn.id="post_"+user_id + "_"+id;
                btn.innerText = `${emoji} ${text}`;
                btn.style.display = "flex";
                btn.style.justifyContent = "center";
                btn.style.alignItems = "center";
                btn.style.cursor = "pointer";
                btn.style.flex = "1";
                btn.style.height = "100%";
                btn.style.transition = "background-color 0.3s, transform 0.1s";
                btn.style.fontWeight = "500";
                btn.addEventListener("mouseover", () => {
                    btn.style.backgroundColor = "#e4e6eb";
                });
                btn.addEventListener("mouseout", () => {
                    btn.style.backgroundColor = "transparent";
                });
                btn.addEventListener("mousedown", () => {
                    btn.style.transform = "scale(0.96)";
                });
                btn.addEventListener("mouseup", () => {
                    btn.style.transform = "scale(1)";
                });

                return btn;
            }

            // Create the buttons
            const like = createButton("Like", "👍");
            const comment = createButton("Comment", "💬");
            const share = createButton("Share", "↗️");

            // Append to parent
            likecomentshare.appendChild(seelike);
            likecomentshare.appendChild(like);
            likecomentshare.appendChild(comment);
            likecomentshare.appendChild(share);
            post1.appendChild(likecomentshare);

            const formdata1 = new FormData();
            const creatorid="post_"+user_id + "_"+id;
            formdata1.append("post12", post1.outerHTML);
            formdata1.append("creator", creatorid);
            formdata1.append("caption", captionText.innerText);

            fetch("post.php", {
                method: "POST",
                body: formdata1,
                credentials: "same-origin"
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("Post saved successfully!");
                } else {
                    alert("Failed: " + data.error);
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred");
            });


        });
    }

        profileDiv.style.height = "300px";
        profileDiv.style.width = "300px";
        profileDiv.style.border = "5px solid #ff0000";
        profileDiv.style.borderRadius = "50%";
        profileDiv.style.marginLeft = "25%";
        img1.style.height = "300px";
        img1.style.width = "300px";
        profileDiv.appendChild(img1);
        cover.appendChild(profileDiv);
            cover.appendChild(caption);
        cover.appendChild(postButton);
        selectContainer.appendChild(cover);
        
    });
    }
    const pside = document.createElement("div");
    pside.style.width = "150px";
    pside.style.height="150px";
    pside.style.display = "block";
    pside.style.paddingLeft="8px"; 
    

    const pname = document.createElement("div");
    pname.style.display="flex";
    pname.style.alignItems="center";
    pname.style.width = "150px";
    pname.style.height = "32%";
    pname.innerText=username;
    pname.style.fontWeight="bold";

    const pflower = document.createElement("div");
    pflower.style.display="flex";
    
    pflower.style.alignItems="center";
    pflower.style.width = "200px";
    pflower.style.height = "32%";
    pflower.style.fontSize = "0.9rem";
    pflower.style.overflow = "visible";
    pflower.style.color="blue";
    pflower.style.fontWeight="bold";
    const flwrform=new FormData();
    flwrform.append('user_id',user_id);
    fetch("flower.php", {
        method: "POST",
        body:flwrform,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            pflower.innerText = `Followers: ${data.follower_count}  Following: ${data.following_count}`;
        } else {
            pflower.innerText = `Error: ${data.message}`;
        }
    })
    .catch(err => {
        console.error("Fetch error:", err);
        pflower.innerText = "Unable to fetch data.";
    });


    const pshowflp = document.createElement("div");
    pshowflp.style.width = "150px";
    pshowflp.style.height = "32%";
    pshowflp.style.display = "flex";
    pshowflp.style.alignItems="center";
    pshowflp.style.overflowX="hide";
    pshowflp.style.position = "relative";
    const allfriends=new FormData();
    allfriends.append('user_id',user_id);
    fetch("allfriends.php", {
        method: "POST",
        body:allfriends,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            data.friends.forEach(friend => {
                const pic = document.createElement('img');
                pic.src = friend.profile_picture;
                pic.style.height = "40px";
                pic.style.width = "40px";
                pic.style.borderRadius = "50%";
                pic.style.boxShadow = "0 0 8px rgba(0, 0, 0, 1)";
                pshowflp.appendChild(pic);
            })
        } else {
            console.error(`Error: ${data.message}`);
            // Or show error in a specific element if needed
        }
    })
    .catch(err => {
        console.error("Fetch error:", err);
    });
    pside.appendChild(pname);
    pside.appendChild(pflower);
    pside.appendChild(pshowflp);
    const alldashbod=document.createElement("div");
    alldashbod.style.width="700px";
    alldashbod.style.height="30px";
    alldashbod.style.marginLeft="17%";
    alldashbod.style.marginTop="1%";
    alldashbod.style.borderRadius="10px";
    alldashbod.style.display="flex";
    alldashbod.style.alignItems="center";
    alldashbod.style.gap="25%";
    alldashbod.style.boxShadow = "0 0 8px rgba(0, 0, 0, 1)";
    const about=document.createElement("button");
    about.style.height="20px";
    about.innerText="About";
    about.style.fontWeight="bold";
    about.style.marginLeft="15%";
    // Photos বোতাম তৈরি
    const photos = document.createElement("button");
    photos.innerText = "Photos";
    photos.style.height = "20px";
    photos.style.fontWeight="bold";
    photos.style.width = "80px";
    photos.style.cursor = "pointer";

    photos.addEventListener("click", function () {
        document.body.innerHTML = ""; 
        document.body.style.display = "block";
        document.body.style.height = "100vh";
        document.body.style.margin = "0";
        document.body.style.backgroundColor = "white";
        const gallery = document.createElement("div");
        gallery.style.display = "grid";
        gallery.style.gridTemplateColumns = "repeat(3, 1fr)";
        gallery.style.top="20px";
        gallery.style.gap = "10px";
        document.body.appendChild(gallery);

        const popup = document.createElement("div");
        popup.style.position = "fixed";
        popup.style.top = "0";
        popup.style.left = "0";
        popup.style.width = "100%";
        popup.style.height = "100%";
        popup.style.background = "rgba(0,0,0,0.8)";
        popup.style.display = "none";
        popup.style.justifyContent = "center";
        popup.style.alignItems = "center";
        document.body.appendChild(popup);

        const popupImage = document.createElement("img");
        popupImage.style.maxWidth = "90%";
        popupImage.style.maxHeight = "90%";
        popup.appendChild(popupImage);
        const user_all_photoform=new FormData();
        user_all_photoform.append('user_id',user_id);

        // ফেচ রিকোয়েস্ট
        fetch("user_all_photo.php", {
            method: "POST",
            body:user_all_photoform,
            credentials: "same-origin"
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                data.images.forEach(src => {
                    const img = document.createElement("img");
                    img.src = src;
                    img.style.width = "100%";
                    img.style.height = "400px";
                    img.style.objectFit = "cover";
                    img.style.borderRadius = "8px";
                    img.style.cursor = "pointer";
                    img.addEventListener("click", function () {
                        popupImage.src = src;
                        popup.style.display = "flex";
                    });
                    gallery.appendChild(img);
                });
            } else {
                console.error("Error:", data.message);
            }
        })
        .catch(err => {
            console.error("Fetch error:", err);
        });

        // পপআপে ক্লিক করলে বন্ধ হবে
        popup.addEventListener("click", function () {
            popup.style.display = "none";
        });
    });


    const vedios=document.createElement("button");
    vedios.style.height="20px";
    vedios.innerText="Videos";
    vedios.style.fontWeight="bold";
    vedios.addEventListener("click", function () {
        document.body.innerHTML = ""; 
        document.body.style.display = "block";
        document.body.style.height = "100vh";
        document.body.style.margin = "0";
        document.body.style.backgroundColor = "white";


        const gallery = document.createElement("div");
        gallery.style.display = "grid";
        gallery.style.gridTemplateColumns = "repeat(3, 1fr)";
        gallery.style.top = "20px";
        gallery.style.gap = "10px";
        document.body.appendChild(gallery);

        // Popup
        const popup = document.createElement("div");
        popup.style.position = "fixed";
        popup.style.top = "0";
        popup.style.left = "0";
        popup.style.width = "100%";
        popup.style.height = "100%";
        popup.style.background = "rgba(0,0,0,0.8)";
        popup.style.display = "none";
        popup.style.justifyContent = "center";
        popup.style.alignItems = "center";
        document.body.appendChild(popup);

        const popupVideo = document.createElement("video");
        popupVideo.style.maxWidth = "90%";
        popupVideo.style.maxHeight = "90%";
        popupVideo.controls = true;
        popup.appendChild(popupVideo);
        const userallvideos=new FormData();
        userallvideos.append('user_id',user_id);
        fetch("user_all_videos.php", {
            method: "POST",
            body:userallvideos,
            credentials: "same-origin"
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                data.videos.forEach(src => {
                    const video = document.createElement("video");
                    video.src = src;
                    video.style.width = "100%";
                    video.style.height = "400px";
                    video.style.objectFit = "cover";
                    video.style.borderRadius = "8px";
                    video.style.cursor = "pointer";
                    video.muted = true; // Gallery-তে sound off
                    video.addEventListener("click", function () {
                        popupVideo.src = src;
                        popup.style.display = "flex";
                        popupVideo.play();
                    });
                    gallery.appendChild(video);
                });
            } else {
                console.error("Error:", data.message);
            }
        })
        .catch(err => {
            console.error("Fetch error:", err);
        });

        // পপআপে ক্লিক করলে বন্ধ হবে
        popup.addEventListener("click", function () {
            popup.style.display = "none";
            popupVideo.pause();
        });
    });
    
    alldashbod.appendChild(about);
    alldashbod.appendChild(photos);
    alldashbod.appendChild(vedios);



    ppdiv.appendChild(ppic);
    ppdiv.appendChild(pside);


    selectContainer.appendChild(nav);
    selectContainer.appendChild(cover);
    const posposrdiv=document.createElement("div");
    posposrdiv.style.width="80%";
    posposrdiv.style.marginLeft="10%";
    posposrdiv.style.marginTop="2%";
    posposrdiv.style.boxShadow="0 0 10px rgba(0,0,0,0.1)"; 

    posposrdiv.innerHTML=htmldata;
    const likeButtons = posposrdiv.querySelectorAll(".Like");
const userId = user_id1; // ✅ একবারেই বাইরে নিয়ে এসেছি, কোটেশন ছাড়া
likeButtons.forEach(button => {
    const creatorId = button.id;

    button.style.fontSize = "1.5rem";
    button.style.position = "relative";
    button.style.overflow = "visible";

    const allreact = document.createElement("div");
    allreact.style.display = 'flex';
    allreact.style.position = "absolute";
    allreact.style.bottom = "100%";
    allreact.style.gap = "10px";

    allreact.innerHTML = `
        <button class="story-reaction" data-reaction="❤️" value="L" style="font-size:1.5rem;">❤️</button>
        <button class="story-reaction" data-reaction="😂" value="H" style="font-size:1.5rem;">😂</button>
        <button class="story-reaction" data-reaction="😮" value="W" style="font-size:1.5rem;">😮</button>
        <button class="story-reaction" data-reaction="😢" value="S" style="font-size:1.5rem;">😢</button>
    `;

    const btns = allreact.querySelectorAll(".story-reaction");

    button.addEventListener("mouseover", function () {
        this.style.backgroundColor = "gray";
        if (!button.contains(allreact)) {
            button.appendChild(allreact);
        }
    });

    button.addEventListener("mouseout", function () {
        this.style.backgroundColor = "";
    });
let type;
    const form = new FormData();
    form.append("user_id", userId);

    fetch(`get_like.php?creator=${creatorId}`, {
        method: "POST",
        body: form
    })
    .then(response => response.json())
    .then(data => {
        console.log("Response:", data);

        if (data.error) {
            console.error("Error:", data.error);
            return;
        }

        let likeclick = data.isgiven? 0 : 1;
        button.innerText = (data.isgiven === "L" ? "❤️" : data.isgiven === "H" ? "😂" : data.isgiven === "W" ? "😮" : data.isgiven === "S" ? "😢" : "👍") + " " + data.like_count;

        // ✅ প্রতিটি রিয়্যাকশন বাটনের জন্য ক্লিক ইভেন্ট
        btns.forEach(rbtn => {
            rbtn.addEventListener("click", function (e) {
                e.stopPropagation();
                button.appendChild(allreact);
                const reaction = this.dataset.reaction;
                type = this.value;
                const form = new FormData();
                form.append("like", 1);
                form.append("creator", creatorId);
                form.append("userid", userId);
                form.append("type", type);
                
                fetch("like.php", {
                    method: "POST",
                    body: form,
                    credentials: "same-origin"
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        button.innerText = reaction + " " + data.like_count;
                        likeclick = 0;
                        console.log("Reaction saved!");
                    } else {
                        alert("Failed: " + data.message);
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                });
            });
        });

        // ✅ আনলাইকের জন্য মেইন বাটন ক্লিক
        button.addEventListener("click", function () {
            if (likeclick === 1) return; // নতুন লাইক না থাকলে কিছু করবেন না

            const form = new FormData();
            form.append("like", -1);
            form.append("creator", creatorId);
            form.append("userid", userId);
            form.append("type", type);

            fetch("like.php", {
                method: "POST",
                body: form,
                credentials: "same-origin"
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    button.innerText = "👍 " ;
                    likeclick = 1;
                    console.log("Unliked successfully");
                } else {
                    alert("Failed to unlike: " + data.message);
                }
            })
            .catch(error => {
                console.error("Error:", error);
            });
        });
    });
});

const commentButtons = posposrdiv.querySelectorAll(".Comment");

commentButtons.forEach(button => {
    button.addEventListener("mouseover", function () {
        this.style.backgroundColor = "gray";
    });

    button.addEventListener("mouseout", function () {
        this.style.backgroundColor = "";
    });

    button.addEventListener("click", function () {
        commentsall(button.id);
    });
});

const shareButtons = posposrdiv.querySelectorAll(".Share"); 
    shareButtons.forEach(button => {
        button.addEventListener("mouseover", function() {
            this.style.backgroundColor = "gray"; // Fixed typo "gary" to "gray"
            
        });
        
        button.addEventListener("mouseout", function() {
            this.style.backgroundColor = ""; 
           // Revert to original color
        });
    });


const seelike = posposrdiv.querySelectorAll(".seelike");

seelike.forEach(button => {
    const id = button.id;
    const form = new FormData();
    form.append("id", id);
    
    fetch("seelike.php", {
        method: "POST",
        body: form
    })
    .then(response => response.json())
    .then(data => {
        console.log("Response react:", data);
        console.log("SERVER RESPONSE:", data); // 👈 Debug

    if (data.error) {
        console.error("Error from server:", data.error);
        return;
    }

    if (!Array.isArray(data) || data.length === 0) {
        console.warn("No reactions to show");
        return;
    }


        button.addEventListener("mouseover", function () {
            this.style.backgroundColor = "gray";
        });

        button.addEventListener("mouseout", function () {
            this.style.backgroundColor = "";
        });

        button.addEventListener("click", function () {
            const container = document.createElement("div");
            container.style.position = "fixed";
            container.style.top = "0";
            container.style.left = "0";
            container.style.width = "100vw";
            container.style.height = "100vh";
            container.style.background = "rgba(0, 0, 0, 0.6)";
            container.style.zIndex = "9999";
            container.style.display = "flex";
            container.style.justifyContent = "center";
            container.style.alignItems = "center";

            const box = document.createElement("div");
            box.style.background = "white";
            box.style.padding = "20px";
            box.style.borderRadius = "12px";
            box.style.width = "360px";
            box.style.maxHeight = "80vh";
            box.style.overflowY = "auto";
            box.innerHTML = `<h3 style="text-align:center;">Reactions</h3><hr>`;

            data.forEach(user => {
                const row = document.createElement("div");
                row.style.display = "flex";
                row.style.alignItems = "center";
                row.style.marginBottom = "12px";

                const img = document.createElement("img");
                img.src = user.profile_picture;
                img.style.width = "40px";
                img.style.height = "40px";
                img.style.borderRadius = "50%";
                img.style.marginRight = "10px";;
                img.addEventListener("click", function() {
                    const profileimgurl2 = user.profile_picture;
                    const user_id2 =user.other_id;
                    const id2 = user.other_id;
                    const username2 = user.name;
                    const form = new FormData();
                    form.append("user_id",user_id2); // FIXED: use noti.sender_id

                    fetch("fetch_content.php", {
                        method: "POST",
                        body: form,
                        credentials: "same-origin"
                    })
                    .then(response => response.json())
                    .then(data => {
                        const htmldata2 = data.posts;
                        profileclick(profileimgurl2, user_id2, id2, username2, htmldata2, fixedid);
                    })
                    .catch(err => console.error(err));
                });

                const name = document.createElement("span");
                name.textContent = user.name;
                name.style.flexGrow = "1";
                name.style.fontWeight = "bold";

                const react = document.createElement("span");
                react.textContent =(user.reaction === "L" ? "❤️" : user.reaction === "H" ? "😂" : user.reaction === "W" ? "😮" : user.reaction === "S" ? "😢" : "👍");
                react.style.fontSize = "20px";

                row.appendChild(img);
                row.appendChild(name);
                row.appendChild(react);

                box.appendChild(row);
            });

            const closeBtn = document.createElement("button");
            closeBtn.textContent = "Close";
            closeBtn.style.marginTop = "15px";
            closeBtn.style.padding = "8px 16px";
            closeBtn.style.cursor = "pointer";
            closeBtn.addEventListener("click", () => container.remove());

            box.appendChild(closeBtn);
            container.appendChild(box);
            document.body.appendChild(container);
        });
    })
    .catch(err => console.error("Fetch error:", err));
});
    selectContainer.appendChild(ppdiv);
    selectContainer.appendChild(alldashbod);
    selectContainer.appendChild(posposrdiv);
    
    const home = document.querySelector(".home");
    home.addEventListener("click", function () {
        location.reload();

    });}
function commentsall(buttonid){
    let comments = '';

    const overlay = document.createElement("div");
    overlay.style.position = "fixed";
    overlay.style.top = "0";
    overlay.style.left = "0";
    overlay.style.width = "100vw";
    overlay.style.height = "100vh";
    overlay.style.background = "rgba(0, 0, 0, 0.7)";
    overlay.style.zIndex = "9999";
    overlay.style.display = "flex";
    overlay.style.flexDirection = "column";
    overlay.style.justifyContent = "center";
    overlay.style.alignItems = "center";

    const box = document.createElement("div");
    box.style.width = "450px";
    box.style.maxHeight = "90vh";
    box.style.overflowY = "auto";
    box.style.background = "#ff6449ff";
    box.style.borderRadius = "10px";
    box.style.padding = "20px";
    box.style.position = "relative";

    const title = document.createElement("h3");
    title.textContent = "Comments";
    title.style.textAlign = "center";
    box.appendChild(title);

    const commentContainer = document.createElement("div");
    commentContainer.style.marginBottom = "15px";
    const cmntform=new FormData();
    cmntform.append("creator", buttonid);
    fetch("getcomment.php",{
        method:"POST",
        body:cmntform,
        credentials:"same-origin"
    }).then(response=>response.json())
    .then(data => {
  if (data.success && Array.isArray(data.data)) {
    data.data.forEach(comment => {
      const el=createCommentElement(
        comment.text, 
        comment.user.profile_picture, 
        comment.user.name, 
        comment.timestamp,
        comment.comment_id,
        comment.comment_id
      );commentContainer.appendChild(el);
    });
  } else {
    console.error('Failed to load comments:', data.error);
  }
})

    box.appendChild(commentContainer);

    const butter = buttonid;
    function createCommentElement(text, profileImg, username, timestamp,comment_id,reply_id) {
        const commentDiv = document.createElement("div");
        commentDiv.style.marginBottom = "30px";
        commentDiv.id="commentDiv ";
        commentDiv.style.paddingLeft = "5px";
        commentDiv.style.background ="white";
        commentDiv.style.width="70%";
        commentDiv.style.borderRadius="10px";
        commentDiv.style.boxShadow = "0 2px 4px rgba(0, 0, 0, 0.82)";

        const img = document.createElement("img");
        img.src = profileImg;
        img.style.width = "40px";
        img.style.height = "40px";
        img.style.borderRadius = "50%";
        img.style.marginRight = "10px";
        img.style.verticalAlign = "middle";

        const name = document.createElement("strong");
        name.textContent = username;

        const p = document.createElement("p");
        p.innerHTML = text; 
        p.style.margin = "3px 0";
        p.style.whiteSpace = "pre-wrap"; 
        p.style.wordWrap = "break-word"; 
        p.style.overflowWrap = "break-word"; 

        const time = document.createElement("small");
        time.textContent = timestamp;
        time.style.fontSize='0.6rem';
        let likeCount = 0;
        const actions = document.createElement("div");
        actions.style.fontSize = "12px";
        actions.style.marginTop = "3px";

        const likeBtn = document.createElement("button");
        likeBtn.id = reply_id;
        likeBtn.textContent = "Like (0)";
        const getlike = new FormData();
        getlike.append("id", likeBtn.id);
        getlike.append("post_id", butter);

                fetch('getcmntlike.php', {
                    method: "POST",
                    body: getlike,
                    credentials: "same-origin"
                })
                .then(response => response.json())
                .then(data => {
                    likeBtn.textContent = `${data.count} ${data.emoji}`;
                })
                .catch(err => {
                    console.error(err);
        });
        likeBtn.style.marginRight = "10px";
        likeBtn.style.position = "relative"; 
        likeBtn.style.overflow = "visible";

        likeBtn.onclick = () => {
            const oldPopup = likeBtn.querySelector(".reaction-popup");
            if (oldPopup) {
                oldPopup.remove();
                return; // toggle effect
            }

            // নতুন popup বানানো
            const allreact = document.createElement("div");
            allreact.className = "reaction-popup";
            allreact.style.display = "flex";
            allreact.style.position = "absolute";
            allreact.style.bottom = "100%";
            allreact.style.left = "0";
            allreact.style.gap = "10px";
            allreact.style.padding = "5px";
            allreact.style.background = "white";
            allreact.style.border = "1px solid #ccc";
            allreact.style.borderRadius = "5px";
            allreact.style.boxShadow = "0px 2px 8px rgba(0,0,0,0.15)";
            allreact.style.zIndex = "999";

            allreact.innerHTML = `
                <button class="story-reaction" data-reaction="❤️" value="L" style="font-size:1.2rem;">❤️</button>
                <button class="story-reaction" data-reaction="😂" value="H" style="font-size:1.2rem;">😂</button>
                <button class="story-reaction" data-reaction="😮" value="W" style="font-size:1.2rem;">😮</button>
                <button class="story-reaction" data-reaction="😢" value="S" style="font-size:1.2rem;">😢</button>
            `;

            allreact.querySelectorAll(".story-reaction").forEach(btn => {
                btn.onclick = (e) => {
                    const emoji = e.currentTarget.dataset.reaction;
                    const getlike=new FormData();
                    getlike.append("id",likeBtn.id);
                    getlike.append("post_id",butter);
                    likeBtn.textContent = `Like ${emoji}`;
                    const likeform=new FormData();
                    likeform.append("id",likeBtn.id);
                    likeform.append("post_id",butter);
                    likeform.append("reaction_type",emoji);
                    fetch("cmntlike.php", {
                        method: "POST",
                        body: likeform,
                        credentials: "same-origin"
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            console.log("Like added successfully");
                        } else {
                            console.error("Error:", data.error);
                        }
                    })
                    .catch(err => {
                        console.error("Error:", err);
                    });
                    allreact.remove(); // popup hide
                };
            });

            likeBtn.appendChild(allreact);
        };

        const replyBtn = document.createElement("button");
        replyBtn.id=reply_id;
        
        replyBtn.textContent = "Reply";
        replyBtn.onclick = () => {
            if (!commentDiv.querySelector(".reply-input")) {
                const replyBox = document.createElement("div");
                replyBox.className = "reply-input";
                replyBox.style.marginTop = "5px";
                const findreply = new FormData();
                findreply.append("reply_id", replyBtn.id);
                findreply.append("post_id", butter);
                findreply.append("comment_id", comment_id);
                fetch("find_reply.php", {
                    method: "POST",
                    body: findreply,
                    credentials: "same-origin"
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && Array.isArray(data.data)) { 
                        data.data.forEach(reply => { 
                            if(String(reply.comment_id) === String(replyBtn.id)) {  
                                const cleanText = reply.text.replace(/["\[\]]/g, '');
                                const mentionHTML = `<span style="color: blue; font-weight: bold;">@${reply.user.name}</span> ${cleanText}`;
                                const el = createCommentElement(
                                    mentionHTML, 
                                    reply.user.profile_picture, 
                                    reply.user.name, 
                                    reply.timestamp,
                                    replyBtn.id,
                                    reply.replyid
                                );
                                replyBox.appendChild(el);
                                commentDiv.style.background =" rgba(255, 7, 7, 0.82)";
                            }
                        });
                    } else {
                        console.error('Failed to load replies:', data.error || 'Unknown error');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                });
                const replyInput = document.createElement("input");
                replyInput.type = "text";
                replyInput.placeholder = "Write a reply...";
                replyInput.style.width = "70%";
                replyInput.style.padding = "5px";

                const replySend = document.createElement("button");
                replySend.textContent = "Send";
                replySend.style.marginLeft = "5px";
                replySend.onclick = () => {
                    const replyText = replyInput.value.trim();
                    if (replyText) {
                        const replyElement = createCommentElement(
                            replyText,
                            profileImageUrl,
                            <?php echo json_encode($user_name); ?>,
                            new Date().toLocaleString(),comment_id,replyBtn.id
                        );
                        repliesContainer.appendChild(replyElement);
                        replyBox.remove();
                    }
                    const replyform=new FormData();
                    replyform.append("post_id",butter);
                    replyform.append("comment_id",comment_id);
                    replyform.append("reply",replyInput.value.trim());
                    fetch("reply_add.php", {
                        method: "POST",
                        body: replyform,
                        credentials: "same-origin"
                        })
                        .then(response => response.json())
                        .then(data => {
                        if (data.success) {
                            console.log("Data added");
                        } else {
                            console.error("Error:", data.error);
                        }
                        })
                        .catch(err => {
                        console.error("Fetch error:", err);
                        });
                };

                replyBox.appendChild(replyInput);
                replyBox.appendChild(replySend);
                commentDiv.appendChild(replyBox);
            }
        };

        actions.appendChild(likeBtn);
        actions.appendChild(replyBtn);

        // Replies container
        const repliesContainer = document.createElement("div");
        repliesContainer.style.marginLeft = "20px";
        repliesContainer.style.marginTop = "5px";

        // Build comment
        commentDiv.appendChild(img);
        commentDiv.appendChild(name);
        commentDiv.appendChild(p);
        commentDiv.appendChild(time);
        commentDiv.appendChild(actions);
        commentDiv.appendChild(repliesContainer);

        return commentDiv;
    }

    // Main comment input
    const mainCommentWrapper = document.createElement("div");
    mainCommentWrapper.style.position = "sticky";
    mainCommentWrapper.style.bottom = "0";
    mainCommentWrapper.style.background = "#fff";
    mainCommentWrapper.style.paddingTop = "10px";
    mainCommentWrapper.style.marginTop = "10px";
    mainCommentWrapper.style.borderTop = "1px solid #ccc";

    const mainInput = document.createElement("input");
    mainInput.type = "text";
    mainInput.placeholder = "Write a comment...";
    mainInput.style.width = "75%";
    mainInput.style.padding = "8px";

    const sendBtn = document.createElement("button");
    sendBtn.textContent = "Send";
    sendBtn.style.padding = "8px 12px";
    sendBtn.style.marginLeft = "5px";
    sendBtn.style.cursor = "pointer";
    sendBtn.onclick = () => {
        const newCommentText = mainInput.value.trim();
        if (newCommentText) {
            const newCommentElement = createCommentElement(
                newCommentText,
                profileImageUrl,
                <?php echo json_encode($user_name); ?>,
                new Date().toLocaleString()
            );
            commentContainer.insertBefore(newCommentElement, commentContainer.firstChild);
            comments=newCommentText;
            mainInput.value = '';
        }
        const id = buttonid;
        const form = new FormData();
        form.append('creator', id);
        form.append('user_id', <?php echo json_encode($user_id); ?>);
        form.append('commentdiv', commentContainer.outerHTML);
        form.append('commenttext',comments);

        fetch("updatecmnt.php", {
            method: "POST",
            body: form,
            credentials: "same-origin"
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
            } else {
                alert("Failed to save: " + data.error);
            }
        });
    };

    mainCommentWrapper.appendChild(mainInput);
    mainCommentWrapper.appendChild(sendBtn);
    box.appendChild(mainCommentWrapper);

    const closeBtn = document.createElement("button");
    closeBtn.textContent = "Close";
    closeBtn.style.marginTop = "15px";
    closeBtn.style.padding = "8px 16px";
    closeBtn.style.cursor = "pointer";
    closeBtn.onclick = () => {
        overlay.remove();
    };

    box.appendChild(closeBtn);
    overlay.appendChild(box);
    document.body.appendChild(overlay);
}
    const home = document.querySelector(".home");
        home.addEventListener("click", function () {
            location.reload();

        });
    // Set the HTML content first
    let dashslot = document.querySelector(".dashslot");

// Fetch the posts via AJAX
fetch('dasbordpost.php')  // You should move your PHP code to a separate file
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success === false) {
            console.error(data.message);
            return;
        }
        dashslot.innerHTML = data.html;
        const likeButtons = dashslot.querySelectorAll(".Like");
        const userId = <?php echo json_encode($user_id); ?>; // ✅ একবারেই বাইরে নিয়ে এসেছি, কোটেশন ছাড়া
        likeButtons.forEach(button => {
            const creatorId = button.id;

            button.style.fontSize = "1.5rem";
            button.style.position = "relative";
            button.style.overflow = "visible";

            const allreact = document.createElement("div");
            allreact.style.display = 'flex';
            allreact.style.position = "absolute";
            allreact.style.bottom = "100%";
            allreact.style.gap = "10px";

            allreact.innerHTML = `
                <button class="story-reaction" data-reaction="❤️" value="L" style="font-size:1.5rem;">❤️</button>
                <button class="story-reaction" data-reaction="😂" value="H" style="font-size:1.5rem;">😂</button>
                <button class="story-reaction" data-reaction="😮" value="W" style="font-size:1.5rem;">😮</button>
                <button class="story-reaction" data-reaction="😢" value="S" style="font-size:1.5rem;">😢</button>
            `;

            const btns = allreact.querySelectorAll(".story-reaction");

            button.addEventListener("mouseover", function () {
                this.style.backgroundColor = "gray";
                if (!button.contains(allreact)) {
                    button.appendChild(allreact);
                }
            });

            button.addEventListener("mouseout", function () {
                this.style.backgroundColor = "";
            });
        let type;
            const form = new FormData();
            form.append("user_id", userId);

            fetch(`get_like.php?creator=${creatorId}`, {
                method: "POST",
                body: form
            })
            .then(response => response.json())
            .then(data => {
                console.log("Response:", data);

                if (data.error) {
                    console.error("Error:", data.error);
                    return;
                }

                let likeclick = data.isgiven? 0 : 1;
                button.innerText = (data.isgiven === "L" ? "❤️" : data.isgiven === "H" ? "😂" : data.isgiven === "W" ? "😮" : data.isgiven === "S" ? "😢" : "👍") + " " + data.like_count;

                // ✅ প্রতিটি রিয়্যাকশন বাটনের জন্য ক্লিক ইভেন্ট
                btns.forEach(rbtn => {
                    rbtn.addEventListener("click", function (e) {
                        e.stopPropagation();
                        button.appendChild(allreact);
                        const reaction = this.dataset.reaction;
                        type = this.value;
                        const form = new FormData();
                        form.append("like", 1);
                        form.append("creator", creatorId);
                        form.append("userid", userId);
                        form.append("type", type);
                        
                        fetch("like.php", {
                            method: "POST",
                            body: form,
                            credentials: "same-origin"
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                button.innerText = reaction + " " + data.like_count;
                                likeclick = 0;
                                console.log("Reaction saved!");
                            } else {
                                alert("Failed: " + data.message);
                            }
                        })
                        .catch(error => {
                            console.error("Error:", error);
                        });
                    });
                });
                button.addEventListener("click", function () {
                    if (likeclick === 1) return; // নতুন লাইক না থাকলে কিছু করবেন না

                    const form = new FormData();
                    form.append("like", -1);
                    form.append("creator", creatorId);
                    form.append("userid", userId);
                    form.append("type", type);

                    fetch("like.php", {
                        method: "POST",
                        body: form,
                        credentials: "same-origin"
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            button.innerText = "👍 " ;
                            likeclick = 1;
                            console.log("Unliked successfully");
                        } else {
                            alert("Failed to unlike: " + data.message);
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                    });
                });
            });
        });

        const commentButtons = dashslot.querySelectorAll(".Comment");

        commentButtons.forEach(button => {
            button.addEventListener("mouseover", function () {
                this.style.backgroundColor = "gray";
            });

            button.addEventListener("mouseout", function () {
                this.style.backgroundColor = "";
            });

            button.addEventListener("click", function () {
                commentsall(button.id)
                
            });

        });

        const shareButtons = dashslot.querySelectorAll(".Share"); 
            shareButtons.forEach(button => {
                button.addEventListener("mouseover", function() {
                    this.style.backgroundColor = "gray"; // Fixed typo "gary" to "gray"
                    
                });
                
                button.addEventListener("mouseout", function() {
                    this.style.backgroundColor = ""; 
                // Revert to original color
                });
            });


        const seelike = dashslot.querySelectorAll(".seelike");

        seelike.forEach(button => {
            const id = button.id;
            const form = new FormData();
            form.append("id", id);
            
            fetch("seelike.php", {
                method: "POST",
                body: form
            })
            .then(response => response.json())
            .then(data => {
                console.log("Response react:", data);
                console.log("SERVER RESPONSE:", data); // 👈 Debug

            if (data.error) {
                console.error("Error from server:", data.error);
                return;
            }

            if (!Array.isArray(data) || data.length === 0) {
                console.warn("No reactions to show");
                return;
            }


                button.addEventListener("mouseover", function () {
                    this.style.backgroundColor = "gray";
                });

                button.addEventListener("mouseout", function () {
                    this.style.backgroundColor = "";
                });

                button.addEventListener("click", function () {
                    const container = document.createElement("div");
                    container.style.position = "fixed";
                    container.style.top = "0";
                    container.style.left = "0";
                    container.style.width = "100vw";
                    container.style.height = "100vh";
                    container.style.background = "rgba(0, 0, 0, 0.6)";
                    container.style.zIndex = "9999";
                    container.style.display = "flex";
                    container.style.justifyContent = "center";
                    container.style.alignItems = "center";

                    const box = document.createElement("div");
                    box.style.background = "white";
                    box.style.padding = "20px";
                    box.style.borderRadius = "12px";
                    box.style.width = "360px";
                    box.style.maxHeight = "80vh";
                    box.style.overflowY = "auto";
                    box.innerHTML = `<h3 style="text-align:center;">Reactions</h3><hr>`;

                    data.forEach(user => {
                        const row = document.createElement("div");
                        row.style.display = "flex";
                        row.style.alignItems = "center";
                        row.style.marginBottom = "12px";

                        const img = document.createElement("img");
                        img.src = user.profile_picture;
                        img.style.width = "40px";
                        img.style.height = "40px";
                        img.style.borderRadius = "50%";
                        img.style.marginRight = "10px";;
                        img.addEventListener("click", function() {
                            const profileimgurl2 = user.profile_picture;
                            const user_id2 =user.other_id;
                            const id2 = user.other_id;
                            const username2 = user.name;
                            const form = new FormData();
                            form.append("user_id",user_id2); // FIXED: use noti.sender_id

                            fetch("fetch_content.php", {
                                method: "POST",
                                body: form,
                                credentials: "same-origin"
                            })
                            .then(response => response.json())
                            .then(data => {
                                const htmldata2 = data.posts;
                                profileclick(profileimgurl2, user_id2, id2, username2, htmldata2, fixedid);
                            })
                            .catch(err => console.error(err));
                        });

                        const name = document.createElement("span");
                        name.textContent = user.name;
                        name.style.flexGrow = "1";
                        name.style.fontWeight = "bold";

                        const react = document.createElement("span");
                        react.textContent =(user.reaction === "L" ? "❤️" : user.reaction === "H" ? "😂" : user.reaction === "W" ? "😮" : user.reaction === "S" ? "😢" : "👍");
                        react.style.fontSize = "20px";

                        row.appendChild(img);
                        row.appendChild(name);
                        row.appendChild(react);

                        box.appendChild(row);
                    });

                    const closeBtn = document.createElement("button");
                    closeBtn.textContent = "Close";
                    closeBtn.style.marginTop = "15px";
                    closeBtn.style.padding = "8px 16px";
                    closeBtn.style.cursor = "pointer";
                    closeBtn.addEventListener("click", () => container.remove());

                    box.appendChild(closeBtn);
                    container.appendChild(box);
                    document.body.appendChild(container);
                });
            })
            .catch(err => console.error("Fetch error:", err));
        });
    })
    .catch(error => {
        console.error('Error:', error);
    });



// Move this to a better location in your script, not at the very end
const search = document.getElementById("search");
    if (search) {
      search.addEventListener("keydown", function (event) {
        if (event.key === "Enter") {
          event.preventDefault();
          const query = search.value.trim();
          if (!query) {
            alert("Please type something to search!");
            return;
          }

          // Clear page and show loading
          document.body.innerHTML = "<div style='text-align:center;padding:30px;'>Searching...</div>";

          // Request to PHP
          const formData = new FormData();
          formData.append("query", query);

          fetch("search.php", {
            method: "POST",
            body: formData
          })
            .then(res => res.json())
            .then(data => {
              if (data.error) {
                alert("Search error: " + data.error);
                return;
              }
              
              // Clear body and add styles
              document.body.innerHTML = "";

              const style = document.createElement("style");
              style.textContent = `
                body {
                  font-family: Arial, sans-serif;
                  background: #f2f2f2;
                  padding: 30px;
                  margin: 0;
                }
                .container {
                  display: grid;
                  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                  gap: 20px;
                }
                .profile-card {
                  background: #fff;
                  border-radius: 12px;
                  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
                  text-align: center;
                  padding: 20px;
                  transition: transform 0.3s;
                }
                .profile-card:hover {
                  transform: scale(1.02);
                }
                .profile-pic {
                  width: 100px;
                  height: 100px;
                  border-radius: 50%;
                  object-fit: cover;
                  border: 3px solid #007bff;
                  margin-bottom: 15px;
                }
                .username {
                  font-size: 18px;
                  font-weight: bold;
                  color: #333;
                  margin-bottom: 10px;
                }
                .buttons {
                  display: flex;
                  justify-content: center;
                  gap: 10px;
                  flex-wrap: wrap;
                }
                .buttons button {
                  padding: 6px 10px;
                  border: none;
                  border-radius: 5px;
                  cursor: pointer;
                  font-weight: bold;
                  transition: 0.3s;
                }
                .add-friend {
                  background-color: #28a745;
                  color: white;
                }
                .message {
                  background-color: #007bff;
                  color: white;
                }
                .add-friend:hover {
                  background-color: #218838;
                }
                .message:hover {
                  background-color: #0056b3;
                }
              `;
              document.head.appendChild(style);
              const user_id=<?php echo json_encode($user_id);?>;
              const container = document.createElement("div");
              container.className = "container";
              if (data.users.length === 0) {
                container.innerHTML = `<div style="text-align:center;padding:30px;">No users found for "${query}"</div>`;
              } else {
                data.users.forEach(user => {
                  const card = document.createElement("div");
                  card.className = "profile-card";

                  const img = document.createElement("img");
                  img.className = "profile-pic";
                  img.src = user.img;
                const profileimgurl2=user.img;// Post create kora
                        const user_id2=user.others_user_id;
                        const id2= user.others_user_id;
                        const username2=user.name;
                img.addEventListener("click",function(){
                    const form=new FormData();
                    form.append("user_id",user.others_user_id)
                    fetch("fetch_content.php",{
                        method:"POST",
                        body:form,
                        credentials:"same-origin"
                    }).then(response=>response.json())
                    .then(data=>{
                        const htmldata2=data.posts;
                        profileclick(profileimgurl2 ,user_id2,id2,username2,htmldata2,fixedid);

                    }).catch(err=>{
                        console.error(err);
                    })
                })
                  const nameDiv = document.createElement("div");
                  nameDiv.className = "username";
                  nameDiv.textContent = user.name;

                  const buttons = document.createElement("div");
                  buttons.className = "buttons";
                  

                  const addFriendBtn = document.createElement("button");
                    addFriendBtn.className = "add-friend";
                    addFriendBtn.id = user.id;
                    addFriendBtn.textContent = user.status;
                    if(user.status==='Add Friend'){
                         addFriendBtn.disabled = false;
                    }else{
                         addFriendBtn.disabled = true;
                    }
                    addFriendBtn.onclick = async () => {
                        try {
                            const form = new FormData();
                            form.append('whom', user.id); // Recipient's ID

                            const response = await fetch("sendfrnd_request.php", {
                                method: "POST",
                                body: form,
                                credentials: "same-origin"
                            });

                            const data = await response.json();
                            
                            if (data.success) {
                                addFriendBtn.textContent = "Request Sent";
                                addFriendBtn.style.backgroundColor = "#6c757d"; // Gray color
                                addFriendBtn.disabled = true;
                                alert(data.message);
                            } else {
                                alert(data.message);
                            }
                        } catch (error) {
                            console.error("Error:", error);
                            alert("Failed to send request");
                        }
                    };

                  const msgBtn = document.createElement("button");
                  msgBtn.className = "message";
                  msgBtn.id=user.id;
                  msgBtn.textContent = "Message";
                  msgBtn.onclick = () => {
                    const form=new FormData();
                    form.append("iduser",msgBtn.id);
                    fetch("searchmsg.php",{
                        method:"POST",
                        body:form,
                        credentials:"same-origin"
                    }).then(response=>response.json())
                    .then(data=>{
                        msgfriendsslot(data.friends.name,data.friends.avatar,data.friends.other_id);
                        const form = new FormData();
                        form.append("sender_id", data.friends.other_id);

                        fetch("add_msgfrnd.php", {
                            method: "POST",
                            body: form,
                            credentials: "same-origin" // session cookie পাঠানোর জন্য দরকার
                        })
                        .then(async response => {
                            const data = await response.json();

                            if (!response.ok) {
                                // সার্ভার 4xx বা 5xx রেসপন্স দিলে এখানে আসবে
                                throw new Error(data.error || "Something went wrong");
                            }

                            console.log(data.message || "Friend added successfully");
                        })
                        .catch(err => {
                            console.error("Error adding messenger friend:", err.message);
                        });

                    }).catch(err=>{
                        console.error('sercherr',err);
                    })
                  }
                  buttons.appendChild(addFriendBtn);
                  buttons.appendChild(msgBtn);

                  card.appendChild(img);
                  card.appendChild(nameDiv);
                  card.appendChild(buttons);

                  container.appendChild(card);
                });
              }

              document.body.appendChild(container);
            })
            .catch(err => {
              console.error("Search failed:", err);
              document.body.innerHTML = "<div style='color:red;text-align:center;padding:30px;'>Search failed</div>";
            });
        }
      });
    }
// Friend Requests Button Functionality
// Friend Requests Button Functionality
const friendsButton = document.querySelector(".friends");

friendsButton.addEventListener("click", function () {
    document.body.innerHTML = "";
    document.body.style.cssText = `
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f0f2f5;
        height: 100vh;
    `;

    const container = document.createElement("div");
    container.id = "friend-requests-container";
    container.style.cssText = `
        max-width: 600px;
        margin: 30px auto;
        background: #fff;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    `;

    const title = document.createElement("h2");
    title.textContent = "Friend Requests";
    title.style.cssText = `
        text-align: center;
        color: #1c1e21;
        margin-bottom: 20px;
        font-size: 24px;
    `;
    container.appendChild(title);

    const loading = document.createElement("div");
    loading.textContent = "Loading friend requests...";
    loading.style.textAlign = "center";
    container.appendChild(loading);

    document.body.appendChild(container);

    fetch("handle_friend_request.php")
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data.requests.length > 0) {
                data.data.requests.forEach(req => {
                    const card = document.createElement("div");
                    card.style.cssText = `
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 15px;
                        margin-bottom: 15px;
                        border: 1px solid #dddfe2;
                        border-radius: 8px;
                        background-color: #f9f9f9;
                    `;

                    const profile = document.createElement("div");
                    profile.style.display = "flex";
                    profile.style.alignItems = "center";

                    const img = document.createElement("img");
                    img.src = req.profile_picture;
                    img.alt = req.name;
                    img.style.cssText = `
                        width: 60px;
                        height: 60px;
                        border-radius: 50%;
                        object-fit: cover;
                        margin-right: 15px;
                    `;
                     img.addEventListener("click", function() {
                        const profileimgurl2 = req.profile_picture;
                        const user_id2 =req.sender_id;
                        const id2 = req.sender_id;
                        const username2 = req.name;
                        const form = new FormData();
                        form.append("user_id",user_id2); // FIXED: use noti.sender_id

                        fetch("fetch_content.php", {
                            method: "POST",
                            body: form,
                            credentials: "same-origin"
                        })
                        .then(response => response.json())
                        .then(data => {
                            const htmldata2 = data.posts;
                            profileclick(profileimgurl2, user_id2, id2, username2, htmldata2, fixedid);
                        })
                        .catch(err => console.error(err));
                    });
                    const nameBox = document.createElement("div");
                    nameBox.innerHTML = `<strong>${req.name}</strong><br><small>Sent: ${new Date(req.created_at).toLocaleDateString()}</small>`;

                    profile.appendChild(img);
                    profile.appendChild(nameBox);

                    const btns = document.createElement("div");
                    btns.style.display = "flex";
                    btns.style.flexDirection = "column";
                    btns.style.gap = "8px";

                    const acceptBtn = document.createElement("button");
                    acceptBtn.textContent = "Confirm";
                    acceptBtn.id=req.sender_id;
                    acceptBtn.style.cssText = `
                        background: #1877f2;
                        color: white;
                        padding: 6px 12px;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                    `;acceptBtn.addEventListener("click", function() {
                    // Disable button during processing
                    acceptBtn.disabled = true;
                    acceptBtn.textContent = "Processing...";

                    const form = new FormData();
                    form.append("sender_id",acceptBtn.id); // Use data-attribute instead of id

                    fetch("accept_friend.php", {
                        method: "POST",
                        body: form,
                        credentials: "same-origin"
                    })
                    .then(response=>response.json()) 
                    .then(data => {
                        if (data.success) {
                            acceptBtn.textContent = "Friends";
                            acceptBtn.style.backgroundColor = "#4CAF50"; // Green for success
                            // Optionally remove the request from UI
                        } else {
                            throw new Error(data.error || "Unknown error occurred");
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        acceptBtn.textContent = "Try Again";
                        acceptBtn.style.backgroundColor = "#f44336"; // Red for error
                        alert("Error accepting request: " + error.message);
                    })
                    .finally(() => {
                        acceptBtn.disabled = false;
                    });
                    });

                    const deleteBtn = document.createElement("button");
                    deleteBtn.textContent = "Delete";
                    deleteBtn.id=req.sender_id;
                    deleteBtn.style.cssText = `
                        background: #e4e6eb;
                        color: #050505;
                        padding: 6px 12px;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                    `;deleteBtn.addEventListener("click",function(){
                        
                    const form = new FormData();
                    form.append("sender_id",deleteBtn.id); // Use data-attribute instead of id

                    fetch("delete_friend.php", {
                        method: "POST",
                        body: form,
                        credentials: "same-origin"
                    })
                    .then(response=>response.json()) 
                    .then(data => {
                        if (data.success) {
                            deleteBtn.textContent = "reject";
                            deleteBtn.style.backgroundColor = "#ff1010ff"; // Green for success
                            // Optionally remove the request from UI
                        } else {
                            throw new Error(data.error || "Unknown error occurred");
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        deleteBtn.textContent = "Try Again";
                        deleteBtn.style.backgroundColor = "#f44336"; // Red for error
                        alert("Error accepting request: " + error.message);
                    })
                    .finally(() => {
                        deleteBtn.disabled = false;
                    });
                    })

                    btns.appendChild(acceptBtn);
                    btns.appendChild(deleteBtn);

                    card.appendChild(profile);
                    card.appendChild(btns);
                    container.appendChild(card);
                });
            } else {
                container.innerHTML += `<p style="text-align:center; color:gray;">No pending friend requests</p>`;
            }
        })
        .catch(error => {
            loading.textContent = "Error loading friend requests.";
            console.error(error);
        });
});
// Notification elements
const notification = document.querySelector(".notificationcount");
const notiDropdown = document.createElement("div");
notiDropdown.id = "notiDropdown";

// Apply styles
Object.assign(notification.style, {
    height: "15px", width: "15px", borderRadius: "50%",
    background: "blue", marginLeft: "80%", display: "flex",
    justifyContent: "center", alignItems: "center",
    color: "white", fontSize: "10px"
});

Object.assign(notiDropdown.style, {
    position: "absolute", right: "20px", top: "60px",
    backgroundColor: "white", borderRadius: "8px",
    boxShadow: "0 2px 10px rgba(0,0,0,0.1)", padding: "10px 0",
    width: "300px", display: "none", zIndex: "1000",
    maxHeight: "400px", overflowY: "auto"
});
document.body.appendChild(notiDropdown);
async function fetchAndDisplayNotifications() {
    try {
        const response = await fetch("notification.php", { credentials: "same-origin" });
        const data = await response.json();
        
        if (data.success) {
            updateNotificationUI(data);
            return true;
        }
        return false;
    } catch (error) {
        console.error("Notification error:", error);
        return false;
    }
}

function updateNotificationUI(data) {
    notification.textContent = data.count || 0;
    notiDropdown.innerHTML = '';
    const header = document.createElement("div");
    header.innerHTML = `
        <div style="font-weight:bold; padding:10px 15px; border-bottom:1px solid #eee; font-size:14px">
            Notifications ${data.count > 0 ? `<span style="float:right; color:blue">${data.count} new</span>` : ''}
        </div>
    `;
    notiDropdown.appendChild(header);
    if (data.notifications.length === 0) {
        const empty = document.createElement("div");
        empty.textContent = "No notifications";
        empty.style.padding = "15px";
        empty.style.textAlign = "center";
        empty.style.color = "#999";
        notiDropdown.appendChild(empty);
    } else {
       data.notifications.forEach(noti => {
        const item = document.createElement("div");
        item.innerHTML = `
            <div style="padding:10px 15px; border-bottom:1px solid #f5f5f5; display:flex; align-items:center; cursor:pointer; transition:background 0.2s">
                <img class="profile-img" 
                    src="${noti.sender_image || 'default-profile.png'}" 
                    style="height:40px; width:40px; border-radius:50%; object-fit:cover; margin-right:12px">
                <div>
                    <div style="font-size:13px; line-height:1.4">
                        <strong>${noti.sender_name}</strong> ${noti.message}
                    </div>
                    <div style="font-size:11px; color:#999; margin-top:3px">
                        ${new Date(noti.time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                    </div>
                </div>
            </div>
        `;
        // Get the image inside this item
        const seeprofile = item.querySelector(".profile-img");
        seeprofile.addEventListener("click", function() {
            const profileimgurl2 = noti.sender_image;
            const user_id2 = noti.sender_id;
            const id2 = noti.sender_id;
            const username2 = noti.sender_name;
            console.log(noti.sender_id);
            const form = new FormData();
            form.append("user_id", noti.sender_id); // FIXED: use noti.sender_id

            fetch("fetch_content.php", {
                method: "POST",
                body: form,
                credentials: "same-origin"
            })
            .then(response => response.json())
            .then(data => {
                const htmldata2 = data.posts;
                profileclick(profileimgurl2, user_id2, id2, username2, htmldata2, fixedid);
            })
            .catch(err => console.error(err));
        });

        notiDropdown.appendChild(item);
    });

    }
}
document.getElementById("notibutton").addEventListener("click", async function(e) {
    e.stopPropagation();
    
    if (notiDropdown.style.display === "none") {
        const success = await fetchAndDisplayNotifications();
        if (success) {
            notiDropdown.style.display = "block";
            await fetch("seen_nitification.php", { method: "POST", credentials: "same-origin" });
        }
    } else {
        notiDropdown.style.display = "none";
    }
});
document.addEventListener("click", () => notiDropdown.style.display = "none");
notiDropdown.addEventListener("click", e => e.stopPropagation());

document.addEventListener('DOMContentLoaded', () => {
    fetchAndDisplayNotifications();
   
    setInterval(fetchAndDisplayNotifications, 30000);
});
</script>
</body>
</html>
<?php $conn->close(); ?> 