<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" />
    <title>404</title>
    <meta name="keywords" content="404">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:500" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Titillium+Web:700,900" rel="stylesheet">

</head>
<style type="text/css">
* { -webkit-box-sizing: border-box; box-sizing: border-box; }
body { padding: 0; margin: 0; }
#notfound { position: relative; height: 100vh; }
#notfound .notfound { position: absolute; left: 50%; top: 50%; -webkit-transform: translate(-50%, -50%); -ms-transform: translate(-50%, -50%); transform: translate(-50%, -50%); text-align: center; }
.notfound { max-width: 767px; width: 100%; line-height: 1.4; padding: 0px 15px; }
.notfound .notfound-404 { position: relative; height: 150px; line-height: 150px; margin-bottom: 25px; display: none; }
.notfound .notfound-404 h1 {font-family: 'Titillium Web', sans-serif; font-size: 186px; font-weight: 900; margin: 0px; text-transform: uppercase; background: url('text.png'); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-size: cover; background-position: center; text-align: center; }
.notfound h2 { font-family: 'Titillium Web', sans-serif; font-size: 26px; font-weight: 700; margin: 0; }
.notfound p { font-family: 'Montserrat', sans-serif; font-size: 14px; font-weight: 500; margin-bottom: 0px; text-transform: uppercase; }
.notfound a { display: inline-block; text-transform: uppercase; color: #fff; text-decoration: none; border: none; background: #840000; padding: 10px 40px; font-size: 14px; font-weight: 700; border-radius: 1px; margin-top: 15px; -webkit-transition: 0.2s all; transition: 0.2s all; font-family: 'Montserrat', sans-serif; margin: 0px auto; }
.notfound a:hover {    /* opacity:0.8; */
border: none; background: #055300; }
@media only screen and (max-width:767px) {
  .notfound .notfound-404 { height: 110px; line-height: 110px; }
  .notfound .notfound-404 h1 { font-size: 120px; }
}
</style>

<body>
    <div id="notfound">
        <div class="notfound">

            <div class="img_404">
                <img src="assets/images/404.gif" alt="404">
            </div>
            <a href="<?=$configBase?>">Come back home !</a>
        </div>
    </div>
</body>

</html>