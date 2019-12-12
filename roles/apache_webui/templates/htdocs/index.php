<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <link rel="icon" type="image/png" href="/main/img/res/mipmap-mdpi/ic_launcher.png" />
    <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet"> 
    <link href="main/manifest.json" rel="manifest">
    <link href="main/css/main.css" rel="stylesheet"> 
    <link href="main/css/panel.css" rel="stylesheet"> 
	<script src="main/js/jquery-3.3.1.js"></script>
	<script src="main/js/vm.touch.js"></script>
	<script src="main/js/vm.widget.js"></script>
	<script src="main/js/vm.widget.panel.js"></script>

	<script type="text/javascript">
        var subMenus = [];
        var $menuPanel = false;
        var isPhone = false;
        var imageTitle = "";
        var activeMenu = "";
        
        var refreshTimer = [];
        
        var netdataTheme = 'default'; // this is white
        var netdataShowAlarms = true;
        var netdataNoBootstrap = true;

        var lang = navigator.language || navigator.userLanguage;

        var translations = null;

        var ressourceCount = 2; //(i18n & background image)

        function loadI18N()
        {
            var url = "";
            if( lang.indexOf("de") === 0 ) url = "index.de.json";
            if( url !== "" )
            {
                var xhr = new XMLHttpRequest();
                xhr.open("GET", url, true);
                xhr.onreadystatechange = function() {
                    if (this.readyState != 4) return;

                    if( this.status == 200 ) translations = JSON.parse(this.responseText);
                    else alert("was not able to download '" + url + "'");
                    initContent();
                };
                xhr.send();
            }
            else
            {
                initContent();
            }
        }

        function getI18N(string)
        {
            if( translations )
            {
                if( typeof translations[string] !== "undefined" )
                {
                    return translations[string];
                }
                else
                {
                    console.error("translation key '" + string + "' not found" );
                }
            }
            return string;
        }

        function handleAlarms(data) 
        { 
            var warnCount = 0;
            var errorCount = 0;
            
            for(x in data.alarms) 
            {
                if(!data.alarms.hasOwnProperty(x)) continue;

                var alarm = data.alarms[x];
                if(alarm.status === 'WARNING')
                {
                    warnCount++;
                }
                if(alarm.status === 'CRITICAL')
                {
                    errorCount++;
                }
            }
        
            $('.alarm.button .badge').text( warnCount + errorCount );

            var $badgeButton = $('.alarm.button');
            if( warnCount > 0 )
            {
                $badgeButton.addClass("warn");
            }
            else
            {
                $badgeButton.removeClass("warn");
            }
            if( errorCount > 0 )
            {
                $badgeButton.addClass("error");
            }
            else
            {
                $badgeButton.removeClass("error");
            }
        }            

        function initAlerting()
        {
            var script = document.createElement("script");
            var timer;
            script.setAttribute("type","text/javascript");
            script.setAttribute("src","/netdata/dashboard.js?v20170724-7");
            script.onload = function()
            {
                window.clearTimeout(timer);
                NETDATA.options.current.destroy_on_hide = false;
                NETDATA.options.current.eliminate_zero_dimensions = true;
                NETDATA.options.current.concurrent_refreshes = false;
                NETDATA.options.current.parallel_refresher = true;
                NETDATA.options.current.stop_updates_when_focus_is_lost = true;
                NETDATA.alarms.server = "/netdata/";
                NETDATA.alarms.callback = handleAlarms;
                
                $('.alarm.button').click(function()
                {
                    openUrl(null,null,"/netdata/",false);
                });
            }
            document.body.appendChild(script);
            timer = window.setTimeout(function() { alert( getI18N("Netdata alert api not avaiable") ); },5000)
        }    
        
        function refreshCamera(container)
        {
            var $image = $(container).find('img');
            var $timeSpan = $(container).find('span.time');
            var $nameSpan = $(container).find('span.name');
            
            var datetime = new Date();
            var h = datetime.getHours();
            var m = datetime.getMinutes();
            var s = datetime.getSeconds();

            var time = ("0" + h).slice(-2) + ':' + ("0" + m).slice(-2) + ':' + ("0" + s).slice(-2);
            $timeSpan.text(time);
            $nameSpan.text($image.attr('data-name'));
            
            var img = new Image();
            var id = Date.now();
            let src = $image.attr('data-src') + '?' + id;
            img.onload = function()
            {
                $image.attr('src',src);
            };
            img.onerror = function()
            {
                $image.attr('src',src);
            };
            img.src = src;
            
            refreshTimer.push( window.setTimeout(function(){refreshCamera(container)},$image.attr('data-interval')) );
        }
        
        function initCamera()
        {
            var $images = $('.service.camera > div');
            $images.each(function(index,container){
                var infoTags = $('<span class="time"></span><span class="name"></span>');
                $(container).append(infoTags);
                refreshCamera(container);
            });
        }
        
        function openMenu(element,key)
        {
            if( activeMenu == key )
            {
              return;
            }
            
            activeMenu = key;
        
            if(refreshTimer.length > 0 )
            {
                for( i = 0; i < refreshTimer.length; i++ )
                {
                    window.clearTimeout(refreshTimer[i]);
                }
                refreshTimer=[];
            }
            
            cleanMenu();
            
            var entries = [];
            var postInit = null;
            
            //console.log(key);
            //console.log(typeof subMenus[key]);
            //console.log(subMenus[key]);
            
            for(var i = 0; i < subMenus[key].length; i++)
            {
                var entry = subMenus[key][i];

                if( entry[0] == 'html' )
                {
                    entries.push('<div class="service ' + key + '">' + entry[2] + '</div>');
                    postInit = this[entry[1]];
                    break;
                }
                else
                {
                    entries.push('<div class="service button ' + key + '" onClick="openUrl(event,this,\'' + entry[1] + '\',' + entry[4] + ')"><div>' + entry[2] + '</div><div>' + entry[3] + '</div></div>');
                }
            }
            
            setMenu(entries.join(""),postInit);
            
            if( element != null ) $(element).addClass("active");
        }
             
        function openHome()
        {
            let isAlreadyActive = ( activeMenu == "home" );
            
            if( !isAlreadyActive )
            {
                activeMenu = "home";
        
                cleanMenu();
            }
            
            var datetime = new Date();
            var h = datetime.getHours();
            var m = datetime.getMinutes();
            var s = datetime.getSeconds();

            var time = ("0" + h).slice(-2) + ':' + ("0" + m).slice(-2);
            
            var prefix = '';
            if(h >= 18) prefix = getI18N('Good Evening');
            else if(h >  12) prefix = getI18N('Good Afternoon');
            else prefix = getI18N('Good Morning');

<?php
    $name = $_SERVER['PHP_AUTH_USER'];
    $handle = fopen(".htdata", "r");
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            list($_username,$_name) = explode(":", $line);
            if( trim($_username) == $_SERVER['PHP_AUTH_USER'] )
            {
                $name = trim($_name);
                break;
            }
        }
    
        fclose($handle);
    }
    echo "            var name = " . json_encode($name) . ";\n";
?>            
            message = '<div class="service home">';
            message += '<div class="time">' + time + '</div>';
            message += '<div class="slogan">' + prefix + ', ' + name + '.</div>';
            message += '<div class="imageTitle">' + imageTitle + '</div>';
            message += '</div>';
            
            if( !isAlreadyActive ) setMenu(message,null);
            else $('#content #submenu').html( message );
            
            refreshTimer.push( window.setTimeout(function(){openHome()},60000 - (s * 1000)) );
        }
        
        function setMenu(data,postInit)
        {
            $('#content #submenu').fadeOut(50, "linear", function() { 
                $('#content #submenu').html( data );
                $('#content #submenu').fadeIn(200);
                if( postInit ) postInit();
            } );

            if( isPhone ) $menuPanel.panel("close");
        }
        
        function cleanMenu()
        {
            $(".service.active").removeClass("active");
        
            $("#content iframe").hide();
            $("#content iframe").attr('src',"about:blank");
            $("#content #inline").show();
        }
       
        function openUrl(event,element,url,newWindow)
        {
            activeMenu = "";
        
            if( (event && event.ctrlKey) || newWindow )
            {
                var win = window.open(url, '_blank');
                win.focus();
            }
            else
            {
                $(".service.active").removeClass("active");
                $("#content #inline").hide();
                $("#content iframe").show();
                $("#content iframe").attr('src',url);
            }
        }

        function initMenus()
        {
            subMenus['nextcloud'] = [
                ['url','/nextcloud/',getI18N('Documents'),'',false],
                ['url','/nextcloud/index.php/apps/news/',getI18N('News'),'',false],
                ['url','/nextcloud/index.php/apps/keeweb/',getI18N('Keys'),getI18N('Keeweb'),true]
            ];
            subMenus['openhab'] = [
                ['url','/basicui/app',getI18N('Control'),getI18N('Basic UI'),false],
                ['url','/paperui/index.html',getI18N('Administration'),getI18N('Paper UI'),false],
                ['url','/habpanel/index.html',getI18N('Tablet UI'),getI18N('HabPanel'),false],
                ['url','/habot',getI18N('Chatbot'),getI18N('Habot'),false]
            ];
            subMenus['roboter'] = [
                ['url','/automowerDevice/',getI18N('Automower'),getI18N('Robonect'),false]
            ];
            subMenus['camera'] = [
                ['html','initCamera','<div class="strasse"><a href="/cameraStrasseDevice/" target="_blank"><img src="/main/img/loading.png" data-name="'+getI18N('Street')+'" data-src="/cameraStrasseImage" data-interval="3000"></a></div><div class="automower"><a href="/automowerDevice/" target="_blank"><img src="/main/img/loading.png"  data-name="'+getI18N('Automower')+'" data-src="/cameraAutomowerImage" data-interval="10000"></a></div>']
            ];
            subMenus['tools'] = [
                ['url','/grafana/d/server-health/server-health',getI18N('Charts'),getI18N('Grafana'),false],
                ['url','/kibana/app/kibana#/dashboard/1431e9e0-1ce7-11ea-8fe5-3b6764e6f175',getI18N('Analytics'),getI18N('Kibana'),false],
//                ['url','/kibana/app/kibana#/dashboard/02e01270-1b34-11ea-9292-eb71d66d1d45',getI18N('Analytics'),getI18N('Kibana'),false],
                ['url','/netdata/',getI18N('State'),getI18N('Netdata'),false]
            ];
            subMenus['admin'] = [
                ['url','/mysql/',getI18N('MySQL'),getI18N('phpMyAdmin'),false],
                ['url','/tools/weatherDetailOverview/', getI18N('Weatherforcast'),getI18N('Meteo Group'),false]
            ];
            subMenus['devices'] = [
                ['url','/redirectToPvInverterGarage', getI18N('Inverter'),getI18N('Solar (Extern)'),true],
                ['url','/redirectToPrinterLaserJet', getI18N('Laserprinter'),getI18N('HPLaserJet'),true],
                ['url','/redirectToFritzBox', getI18N('Router'),getI18N('FritzBox'),true]
            ];
        }

        function initContent()
        {
            ressourceCount--;
            if( ressourceCount > 0 ) return;

            var elements = document.querySelectorAll("*[data-i18n]");
            elements.forEach(function(element)
            {
                var key = element.getAttribute("data-i18n");
                element.innerHTML = getI18N(key);
            });

            initMenus();

            $('#logo').click(function() {
                openHome();
            });

            if( !activeMenu ) openHome();
        }

        function initPage()
        {
            function isPhoneListener(mql){
                isPhone=mql.matches;
                if( isPhone )
                {
                    $('body').addClass('phone');
                    $('body').removeClass('desktop');
                }
                else
                {
                    $('body').removeClass('phone');
                    $('body').addClass('desktop');
                }
            }
            var mql = window.matchMedia('(max-width: 600px)');
            mql.addListener(isPhoneListener);
            isPhoneListener(mql);
            
            $menuPanel = $('#menu');
            $menuPanel.panel({
                defaults: true,
                display: isPhone ? "overlay" : "inline",
                dismissible: isPhone ? true : false,
                positionFixed: true,
                beforeopen: function() {
                    $('.burger.button').addClass("open");
                    $("#side").removeClass("fullsize");
                },
                beforeclose: function() {
                    $('.burger.button').removeClass("open");
                    $("#side").addClass("fullsize");
                }
            });
            $('.burger.button').click(function() {
                $menuPanel.panel("toggle");
            });
            
            if( !isPhone )
            {
                var menuMql = window.matchMedia('(min-width: 800px)');
                function checkMenu()
                {
                    $menuPanel.panel( menuMql.matches ? "open" : "close" );
                }
                menuMql.addListener(checkMenu);
                checkMenu();
            }
            
            var id = Math.round( Date.now() / 1000 / ( 60 * 60 ) );
            var src = "/img/potd/today" + ( isPhone ? "Portrait" : "Landscape") + ".jpg?" + id;
            var img = new Image();
            img.onload = function()
            {
                $("body").removeClass("nobackground" );
                $("#background").css("background-image", "url(" + src + ")" );
                $("#background").css("opacity", "1.0" );
                
                //openMenu($('#defaultEntry'),"nextcloud");
                var xhr = new XMLHttpRequest();
                xhr.open("GET", "/img/potd/todayTitle.txt?" + id);
                xhr.onreadystatechange = function() {
                    if (this.readyState != 4) return;
                    
                    if( this.status == 200 ) imageTitle = this.response;
                    else alert("was not able to download '/img/potd/todayTitle.txt?'");
                    initContent();
                };
                xhr.send();
            };
            img.onerror = function()
            {
                $("#background").css("opacity", "1.0" );
                $("body").addClass("nobackground" );
                initContent();
            };
            img.src = src;

            initAlerting();
        }

        loadI18N();
        $(document).ready(initPage);

	</script>
</head>
<body>
<div id="page">
    <div id="menu" data-role="panel">
        <div class="group">
            <div id="logo" class="button"></div>
            <div class="spacer"></div>
            <div class="alarm button">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M433.884 366.059C411.634 343.809 384 316.118 384 208c0-79.394-57.831-145.269-133.663-157.83A31.845 31.845 0 0 0 256 32c0-17.673-14.327-32-32-32s-32 14.327-32 32c0 6.75 2.095 13.008 5.663 18.17C121.831 62.731 64 128.606 64 208c0 108.118-27.643 135.809-49.893 158.059C-16.042 396.208 5.325 448 48.048 448H160c0 35.346 28.654 64 64 64s64-28.654 64-64h111.943c42.638 0 64.151-51.731 33.941-81.941zM224 472a8 8 0 0 1 0 16c-22.056 0-40-17.944-40-40h16c0 13.234 10.766 24 24 24z"></path></svg><span class="badge">0</span>
            </div>
            <div class="burger button">
                <svg style="fill:white;stroke:white;" transform="scale(1.0)" enable-background="new 0 0 91 91" id="Layer_1" version="1.1" viewBox="0 0 91 91" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><rect height="3.4" width="39.802" x="27.594" y="31.362"/><rect height="3.4" width="39.802" x="27.594" y="44.962"/><rect height="3.4" width="39.802" x="27.594" y="58.562"/></g></svg>
            </div>
        </div>
        <div class="group flexInfo autoWidth">
            <div class="header" data-i18n="NextCloud"></div>
            <div class="service button" id="defaultEntry" onClick="openMenu(this,'nextcloud')"><div data-i18n="NextCloud"></div><div></div></div>
        </div>
        <div class="group">
            <div class="header" data-i18n="Automation"></div>
            <div class="service button" onClick="openMenu(this,'openhab')"><div data-i18n="Openhab"></div><div></div></div>
            <div class="service button" onClick="openMenu(this,'roboter')"><div data-i18n="Roboter"></div><div></div></div>
            <div class="service button" onClick="openMenu(this,'camera')"><div data-i18n="Cameras"></div><div></div></div>
        </div>
        <div class="group flexInfo">
            <div class="header" data-i18n="Administration"></div>
            <div class="service button" onClick="openMenu(this,'tools')"><div data-i18n="Logs &amp; States"></div><div></div></div>
            <div class="service button" onClick="openMenu(this,'admin')"><div data-i18n="Tools"></div><div></div></div>
            <div class="service button" onClick="openMenu(this,'devices')"><div data-i18n="Devices"></div><div></div></div>
        </div>
    </div>
    <div id="side" data-role="page" class="fullsize">
        <div id="header" data-role="header">
            <div class="burger button">
                <svg style="fill:white;stroke:white;" transform="scale(1.0)" enable-background="new 0 0 91 91" id="Layer_1" version="1.1" viewBox="0 0 91 91" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><rect height="3.4" width="39.802" x="27.594" y="31.362"/><rect height="3.4" width="39.802" x="27.594" y="44.962"/><rect height="3.4" width="39.802" x="27.594" y="58.562"/></g></svg>
            </div>
            <div class="alarm button">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M433.884 366.059C411.634 343.809 384 316.118 384 208c0-79.394-57.831-145.269-133.663-157.83A31.845 31.845 0 0 0 256 32c0-17.673-14.327-32-32-32s-32 14.327-32 32c0 6.75 2.095 13.008 5.663 18.17C121.831 62.731 64 128.606 64 208c0 108.118-27.643 135.809-49.893 158.059C-16.042 396.208 5.325 448 48.048 448H160c0 35.346 28.654 64 64 64s64-28.654 64-64h111.943c42.638 0 64.151-51.731 33.941-81.941zM224 472a8 8 0 0 1 0 16c-22.056 0-40-17.944-40-40h16c0 13.234 10.766 24 24 24z"></path></svg><span class="alarms_count_badge badge">0</span>
            </div>
        </div>
        <div id="content" data-role="main">
            <div id="inline">
                <div id="background"></div>
                <div id="submenu"></div>
            </div>
            <iframe frameborder="0">
        </div>
    </div>
</div>
</body>
</html>