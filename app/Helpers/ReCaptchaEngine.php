<?php
namespace App\Helpers;
use Illuminate\Support\HtmlString;

class ReCaptchaEngine
{
    protected static $version = "v2";
    protected static $api_key;
    protected static $api_secret;
    protected static $is_init;
    protected static $actions = [];
    protected static $is_enable = false;

    public static function scripts()
    {
        if (!self::isEnable() OR empty(static::$actions))
            return false;

        if(self::$version=='v3'){
            return  self::scriptsV3();
        }
        ?>
        <script src="https://www.google.com/recaptcha/api.js?onload=BravoReCaptchaCallBack&render=explicit" async defer></script>
        <script>
            window.BravoReCaptcha = {
                is_loaded : false,
                actions: <?php echo json_encode(static::$actions) ?>,
                widgetIds : {},
                sitekey:'<?php echo e(self::$api_key) ?>',
                callback: function () {
                    this.is_loaded = true;

                    for (var k in this.actions) {
                        var id = grecaptcha.render(this.actions[k],{
                            sitekey:this.sitekey,
                            callback:this.validateCallback
                        });
                        this.widgetIds[k] = id;
                    }
                },
                reset(action) {
                    if (typeof this.widgetIds[action] !== 'undefined') {
                        grecaptcha.reset(this.widgetIds[action]);
                    }
                },
                getToken(action) {
                    if (typeof this.widgetIds[action] === 'undefined') {
                        return '';
                    }
                    return grecaptcha.getResponse(this.widgetIds[action]);
                },
                validateCallback(){

                }
            }

            function BravoReCaptchaCallBack(){
                BravoReCaptcha.callback();
            }
        </script>
        <?php
    }


    public static function captcha($action = 'default')
    {
        if (!self::isEnable())
            return false;

        static::$actions[$action] = $action . '_' . uniqid();

        if(self::$version=='v3'){
            return new HtmlString('<input type="hidden" name="g-recaptcha-response" class="bravo-recaptcha" id="'.e(static::$actions[$action]).'"><!--End Captcha-->');
        }else{
            return new HtmlString('<div class="bravo-recaptcha" id="'.e(static::$actions[$action]).'"></div><!--End Captcha-->');
        }
    }

    public static function scriptsV3()
    {
        if (!self::isEnable() OR empty(static::$actions))
            return false;


        ?>

        <script src="https://www.google.com/recaptcha/api.js?render=<?php echo e(self::$api_key) ?>&onload=BravoReCaptchaCallBack" async defer></script>
        <script>

            window.BravoReCaptcha = {
                is_loaded : false,
                actions: <?php echo json_encode(static::$actions) ?>,
                widgetIds : {},
                sitekey:'<?php echo e(self::$api_key) ?>',
                callback: function () {
                    this.is_loaded = true;
                    for (var k in this.actions) {
                        var v = this.actions[k]
                        this.getToken(k,v)
                        this.widgetIds[k] = v;
                    }
                },
                reset(action) {
                    this.getToken(action,this.widgetIds[action])
                },
                getToken(action,id) {
                    grecaptcha.ready(function () {
                        grecaptcha.execute('<?php echo e(self::$api_key) ?>', {action: action}).then(function(token) {
                            $('#'+id).val(token);
                        })
                    });
                },
                validateCallback(){
                }
            }

            function BravoReCaptchaCallBack(){
                BravoReCaptcha.callback();
            }
        </script>
        <?php
    }



    public static function isEnable()
    {
        self::maybeInit();
        if (!self::$api_key or !self::$api_secret or !self::$is_enable)
            return false;
        return true;
    }

    public static function isRequiredForAuth()
    {
        return self::isEnable();
    }

    public static function maybeInit()
    {
        if (self::$is_init)
            return;
        $keys = self::resolveKeys(self::detectEnvironment());
        self::$api_key = $keys['site_key'];
        self::$api_secret = $keys['secret_key'];
        self::$is_enable = setting_item('recaptcha_enable');
        self::$is_init = true;
        self::$version = setting_item('recaptcha_version','v2');
    }

    public static function detectEnvironment()
    {
        $env = strtolower((string) config('app.env'));
        $url = strtolower((string) config('app.url'));
        $host = '';

        if (!app()->runningInConsole()) {
            try {
                $host = strtolower((string) request()->getHost());
            } catch (\Throwable $e) {
                $host = '';
            }
        }

        $haystack = $host.' '.$url;

        if (str_contains($haystack, 'laratest') || in_array($env, ['laratest', 'staging'], true)) {
            return 'laratest';
        }

        $isLocalHost = in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.local')
            || str_contains($haystack, 'localhost');

        if ($env === 'local' || $isLocalHost) {
            return 'local';
        }

        return 'live';
    }

    protected static function resolveKeys($environment)
    {
        $site = config('services.recaptcha.site_key');
        $secret = config('services.recaptcha.secret_key');
        if (!empty($site) && !empty($secret)) {
            return [
                'site_key' => $site,
                'secret_key' => $secret,
            ];
        }

        $envSite = config('services.recaptcha.'.$environment.'.site_key');
        $envSecret = config('services.recaptcha.'.$environment.'.secret_key');
        if (!empty($envSite) && !empty($envSecret)) {
            return [
                'site_key' => $envSite,
                'secret_key' => $envSecret,
            ];
        }

        $settingMap = [
            'local' => ['recaptcha_api_key_local', 'recaptcha_api_secret_local'],
            'laratest' => ['recaptcha_api_key_laratest', 'recaptcha_api_secret_laratest'],
            'live' => ['recaptcha_api_key', 'recaptcha_api_secret'],
        ];

        $names = $settingMap[$environment] ?? $settingMap['live'];
        $site = setting_item($names[0]);
        $secret = setting_item($names[1]);
        if (!empty($site) && !empty($secret)) {
            return [
                'site_key' => $site,
                'secret_key' => $secret,
            ];
        }

        return [
            'site_key' => setting_item('recaptcha_api_key'),
            'secret_key' => setting_item('recaptcha_api_secret'),
        ];
    }

    public static function verify($response)
    {
        if (!self::isEnable())
            return true;
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret'   => self::$api_secret,
            'response' => $response
        ];
        $verify = static::file_get_contents_curl($url, true, $data);

        $captchaVerify = json_decode($verify, true);
        if (is_array($captchaVerify) && !empty($captchaVerify['success'])) {
            return true;
        }
        return false;
    }

    public static function file_get_contents_curl($url,$isPost = false,$data = []) {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

        if($isPost){
            curl_setopt($ch, CURLOPT_POST, count($data));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }
}
