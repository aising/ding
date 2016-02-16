<!DOCTYPE html>

<!--[if IE 8]> <html lang="en" class="ie8"> <![endif]-->

<!--[if IE 9]> <html lang="en" class="ie9"> <![endif]-->

<!--[if !IE]><!--> <html> <!--<![endif]-->

    <!-- BEGIN HEAD -->

    <head>

        <meta charset="utf-8" />

        <title><?php echo Doo::conf()->siteName ?></title>

        <meta content="width=device-width, initial-scale=1.0" name="viewport" />

        <meta content="" name="description" />

        <meta content="" name="author" />

        <!-- BEGIN GLOBAL MANDATORY STYLES -->

        <link href="<?php echo Doo::conf()->global ?>bootstrap/media/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>

        <link href="<?php echo Doo::conf()->global ?>bootstrap/media/css/bootstrap-responsive.min.css" rel="stylesheet" type="text/css"/>

        <link href="<?php echo Doo::conf()->global ?>bootstrap/media/css/font-awesome.min.css" rel="stylesheet" type="text/css"/>

        <link href="<?php echo Doo::conf()->global ?>bootstrap/media/css/style-metro.css" rel="stylesheet" type="text/css"/>

        <link href="<?php echo Doo::conf()->global ?>bootstrap/media/css/style.css" rel="stylesheet" type="text/css"/>

        <link href="<?php echo Doo::conf()->global ?>bootstrap/media/css/style-responsive.css" rel="stylesheet" type="text/css"/>

        <link href="<?php echo Doo::conf()->global ?>bootstrap/media/css/default.css" rel="stylesheet" type="text/css" id="style_color"/>

        <link href="<?php echo Doo::conf()->global ?>bootstrap/media/css/uniform.default.css" rel="stylesheet" type="text/css"/>

        <!-- END GLOBAL MANDATORY STYLES -->

        <!-- BEGIN PAGE LEVEL STYLES -->

        <link href="<?php echo Doo::conf()->global ?>bootstrap/media/css/login.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo Doo::conf()->global ?>css/base.css" rel="stylesheet" type="text/css"/>
        <!-- END PAGE LEVEL STYLES -->

        <link rel="shortcut icon" href="<?php echo Doo::conf()->global ?>bootstrap/media/image/favicon.ico" />
        <script type="text/javascript">
            var BASEURL = "<?php echo Doo::conf()->APP_URL ?>";
        </script>
    </head>

    <!-- END HEAD -->

    <!-- BEGIN BODY -->

    <body class="login">
        <!--[if lt IE 8]> 
                <div class="alert alert-error" style="text-align:center">
                        <button class="close" data-dismiss="alert"></button>
                        浏览器版本过低，请使用ie9+、chrome、firefox等现代浏览器或其他浏览器的疾速模式！
                </div>
        <![endif]-->

        <!-- BEGIN LOGO -->

        <div class="logo">

                <!--<img src="<?php echo Doo::conf()->global ?>bootstrap/media/image/logo-big.png" alt="" style="vertical-align:middle;" /> -->

            <span><?php echo Doo::conf()->siteName ?></span>

        </div>

        <!-- END LOGO -->

        <!-- BEGIN LOGIN -->

        <div class="content">

            <!-- BEGIN LOGIN FORM -->

            <!-- <form class="form-vertical login-form" id="js-form" method="post" action=""> -->
            <?php echo $data['startDooForm'] ?>
            <h3 class="form-title">登录你的账号</h3>


            <div id="js-form-errors" class="" style="display: none" ></div>

            <div class="control-group">

                <!--ie8, ie9 does not support html5 placeholder, so we just show field title for that-->

                <label class="control-label visible-ie8 visible-ie9">用户名</label>

                <div class="controls">

                    <div class="input-icon left">

                        <i class="icon-user"></i>

                                                <!--<input class="m-wrap placeholder-no-fix" type="text" placeholder="Username" name="username" value="admin"/>-->
                        <?php echo $data['elements']['username'] ?>
                        <?php //D($data)?>
                    </div>

                </div>

            </div>

            <div class="control-group">

                <label class="control-label visible-ie8 visible-ie9">密码</label>

                <div class="controls">

                    <div class="input-icon left">

                        <i class="icon-lock"></i>

                                                <!--<input class="m-wrap placeholder-no-fix" type="password" placeholder="Password" name="password"  value="123456"/>-->
                        <?php echo $data['elements']['password'] ?>
                    </div>

                </div>

            </div>

            <div class="control-group">

                <label class="control-label visible-ie8 visible-ie9">语言</label>

                <div class="controls">

                    <div class="input-icon left">
                        <?php echo $data['elements']['lang'] ?>
                    </div>

                </div>

            </div>



            <div class="control-group">

                <label class="control-label visible-ie8 visible-ie9">验证码</label>

                <div class="controls">

                    <div class="input-icon left">

                        <i class="icon-lock"></i>
                        <?php echo $data['elements']['safecode'] ?>
                        <span class="help-inline"><img id="js-safeCode" src="<?php echo appurl('captcha')?>" onclick="this.src = '<?php echo appurl('captcha?q=')?>' + Math.random()" alt=""></span>            

                    </div>

                </div>

            </div>

            <div class="form-actions">
                <button type="submit" class="btn green pull-right">

                    登录 <i class="m-icon-swapright m-icon-white"></i>

                </button>            

            </div>

            <?php echo $data['endDooForm'] ?>
            <!-- END LOGIN FORM -->        




        </div>

        <!-- END LOGIN -->

        <!-- BEGIN COPYRIGHT -->

        <div class="copyright">


        </div>

        <!-- END COPYRIGHT -->

        <!-- BEGIN JAVASCRIPTS(Load javascripts at bottom, this will reduce page load time) -->

        <!-- BEGIN CORE PLUGINS -->

        <script src="<?php echo Doo::conf()->global ?>bootstrap/media/js/jquery-1.10.1.min.js" type="text/javascript"></script>
        <script src="<?php echo Doo::conf()->global ?>bootstrap/media/js/bootstrap.min.js" type="text/javascript"></script>
        <script src="<?php echo Doo::conf()->global ?>bootstrap/media/js/jquery.form.min.js" type="text/javascript"></script>
        <script src="<?php echo Doo::conf()->global ?>js/tcaplus-base.js" type="text/javascript"></script>

        <script type="text/javascript">
            $(document).ready(
                    function()
                    {
                        BASE.ajaxForm({
                            showType: 'redirect',
                            redirect: location.href,
                            errorCustomCallBack:function(def) {
                                $('#js-safeCode').click();
                            }
                        });
                    });
        </script>
      

        <!-- END JAVASCRIPTS -->

        <!-- END BODY -->

</html>