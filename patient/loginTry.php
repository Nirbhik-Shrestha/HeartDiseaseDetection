<?php ?>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>

    *{
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Platin', Times, serif;
    }

    .container{
        width: 100%;
        height: 100%;
        background-image: linear-gradient(rgba(0,0,50,0.8),rgba(0,0,50,0.8)), url('../images/bg3.jpg');
        background-size: cover;
        background-position: center;
        position: relative;
    }

    .form-box{
        width: 90%;
        position: absolute;
        max-width: fit-content;
        top: 50%;
        left: 50%;
        transform: translate(-50%,-50%);
        background: #fff;
        padding: 50px 60px 70px;
        text-align: center;
    }

    .form-box h1{
        font-size: 30px;
        margin-bottom: 60px;
        color: #3c00a0;
        position: relative;
    }
    
    .form-box h1::after{
        content: '';
        width: 30px;
        height: 4px;
        border-radius: 3px;
        background: #3c00a0;
        position: absolute;
        bottom: -12px;
        left: 50%;
        transform: translateX(-50%);
    }

    .input-field{
        background: #eaeaea;
        margin: 15px 0;
        border-radius: 3px;
        display: flex;
        align-items: center;
        max-height: 65px;
        transition: max-height 0.5s;
        overflow: hidden;
        
    }

    input{
        width: 100%;
        background: transparent;
        border: 0;
        outline: 0;
        padding: 18px 15px;
    }

    form p{
        display: none;
        text-align: left;
        font-size: 13px;
        margin-bottom: 20px;
    }

    form p a{
        text-decoration: none;
        color: #3c00a0; 
    }

    .btn-field{
        width: 100%;
        display: flex;
        justify-content: space-between;
    }

    .btn-field button{
        flex-basis: 48%;
        background: #3c00a0;
        color: #fff;
        height: 40px;
        border-radius: 20px;
        border: 0;
        outline: 0;
        cursor: pointer;
        transition: background 1s;
    }

    .input-group{
        height: 280px;
        display: flex;
        flex-wrap: wrap;
        flex-direction: column;
        gap: 0px 20px;
        }

    .btn-field button.disable{
        background: #eaeaea;
        color: #555;
    }

</style>

</head>
<body>

<div class="container">
    <div class="form-box" id="formbox">
        <h1 id="title">Sign Up</h1>
        <form action="loginTry.php" method="POST">
            <div class="input-group">
                <!-- <div id="hiddenFieldName"> -->
                <div class="input-field" id="hiddenField1">
                    <input type="text" name="pname" placeholder="Name" required>
                </div>
                <!-- </div> -->

                <div class="input-field">
                    <input type="email" name="pemail" placeholder="Email" required>
                </div>

                <div class="input-field">
                    <input type="password" name="ppassword" placeholder="Password" required>
                </div>

                <!-- <div id="hiddenField"> -->
                <div class="input-field" id="hiddenField2">
                    <input type="text" name="pcontact" placeholder="Contact" id="contactNumber" required>
                </div>
                
                <div class="input-field" id="hiddenField3">
                    <input type="text" name="paddress" placeholder="Address" required>
                </div>
                
                <div class="input-field" id="hiddenField4">
                    <input type="date" name="pdob" placeholder="Date of Birth" required>
                </div>
                <!-- </div> -->
                
                
            </div>
                
                
            <p id="forgotPass">Forgot Password? <a href='#'>Click Here!</a></p>
            
            <div class="btn-field">
                <button type="button" id="signUpBtn">Sign Up</button>
                <button type="button" id="signInBtn" class="disable">Sign In</button>
            </div>

            <!-- <div class="input-field">
                <input type="submit" name="register" class="button-25" value="Sign Up">
            </div> -->
        </form>
    </div>
</div>

<script>

// let signUpBtn = document.getElementById("signUpBtn");
// let signInBtn = document.getElementById("signInBtn");
// let hiddenField = document.getElementById("hiddenField");
// let hiddenFieldName = document.getElementById("hiddenFieldName");
// let title = document.getElementById("title");
// let formbox = document.getElementById("formbox");

signInBtn.onclick = function(){
    hiddenField1.style.display = 'none';
    hiddenField2.style.display = 'none';
    hiddenField3.style.display = 'none';
    hiddenField4.style.display = 'none';
    title.innerHTML = "Sign In";
    signUpBtn.classList.add("disable");
    signInBtn.classList.remove("disable");
    formbox.style.maxWidth = '450px'; 
    forgotPass.style.display = 'block';
}

signUpBtn.onclick = function(){
    hiddenField1.style.display = 'block';
    hiddenField2.style.display = 'block';
    hiddenField3.style.display = 'block';
    hiddenField4.style.display = 'block';
    title.innerHTML = "Sign Up";
    signInBtn.classList.add("disable");
    signUpBtn.classList.remove("disable");
    formbox.style.maxWidth = 'fit-content';
    forgotPass.style.display = 'none';
}


</script>



</body>
</html>