<?php

/**
 * 控制器
 */
class Action
{
    /**
     * 模块在 mvc 中初始化的时候，会被重设为正确的路径
     * 作为一个属性，在不同的 App 同名的 Action 中，比作为静态属性有用
     * @var string
     */
    public $modulePath = '';

    /**
     * 模块名
     * @var string
     */
    public $moduleName = 'index';

    /**
     * 当前执行的方法名，仅在 mvc 中执行外部调用的时候被设置
     * @var string
     */
    public $methodName = 'index';

    /**
     * 当前的类名
     * @var string
     */
    public $className = 'indexAction';

    /**
     * 模板变量，display之后被清空
     * @var array
     */
    public $displayVal = array();

    /**
     * 视图模板的布局模版
     * @var null
     */
    public $layoutName = null;

    /**
     * Action constructor. do nothing in here
     */
    function __construct()
    {

    }

    /**
     * 过滤函数，传入当前的  $methodName 返回的值若为 false 则系统停止执行，并触发异常
     * @param bool|string $methodName
     * @return bool
     */
    function filterAccess($methodName = true)
    {
        return $methodName;
    }

    /**
     * 模板变量赋值
     * @param  string|array $n 字符串情况下会以 $n 为键，后续的 $v 为值，为数组，则是批量赋值
     * @param  mixed|null $v 单个模板变量的值
     * @return array    模板变量
     */
    function assign($n, $v = null)
    {
        if (is_array($n)) {
            $this->displayVal = array_merge($this->displayVal, $n);
        } else {
            $this->displayVal[$n] = $v;
        }
        return $this->displayVal;
    }


    /**
     * 快速重定向到URL
     * @param  string $url URL地址
     * @return null
     */
    function redirect($url = '')
    {
        header("location:" . $url . "");
        return true;
    }

    /**
     * 设置模版布局，和当前的 Method 绑定
     * @param string $name
     * @return string
     */
    function layout($name = '')
    {
        $this->layoutName[$this->methodName] = $name;
        return $name;
    }


    /**
     * 为 smartyFetch 提供 Smarty 类，这个类是由核心函数自动加载的
     * @return Smarty
     */
    function smartyInit()
    {
        if (!isset($this->smarty)) {
            $smarty = new Smarty;
            $this->smarty = $smarty;
            $smarty->setCompileDir(sys_get_temp_dir() . '/smarty-compile');
            $smarty->setCacheDir(sys_get_temp_dir() . '/smarty-cache');

            mvc::log('setTemplateDir -> ' . sys_get_temp_dir());
            mvc::log('setCompileDir & setCacheDir ->' . sys_get_temp_dir());
        }
        return $this->smarty;
    }

    /**
     * TPL_ENGINE 为 smarty 时 fetch 获取内容会执行此函数
     * @param string $name
     * @return string
     */
    function  smartyFetch($name = '')
    {
        $smarty = $this->smartyInit();
        $pathFinal = $this->_inside_call_makeTemplateFinalPath($name);
        $smarty->setTemplateDir(dirname($pathFinal));
        foreach ($this->displayVal as $k => $v) {
            $smarty->assign($k, $v);
        }
        return $this->_inside_call_replaceTemplateUrl($smarty->fetch(basename($pathFinal)));
    }

    /**
     * 获取模板字符串
     * @param string $name
     * @param bool|array|string $isInclude 如果作为一个数组，则模版的变量将完全使用这个数组
     * @return mixed|string
     * @throws Exception
     */
    function fetch($name = '', $isInclude = false)
    {
        if (C('TPL_ENGINE') === 'smarty') {
            return $this->smartyFetch($name, $isInclude);
        }
        mvc::log('fetch:' . $name);
        if (is_array($isInclude)) {
            $this->_displayVal = $isInclude;
            $isInclude = false;
        }

        $pathFinal = $this->_inside_call_makeTemplateFinalPath($name);

        if (!$pathFinal) {
            return 'Template: <font color="red">' . $name . '</font> is non-existent!';
        }

        $content = $this->_inside_call_replaceTemplateUrl(file_get_contents($pathFinal));

        /**
         * 处理引用
         * 引用文件不存在时
         * 由PHP的include默认错误进行处理
         */
        $content = preg_replace_callback('/' . mvc::config('TPL_LEFT_DELIMITER') . '\s?include\s+([^}]*)\s?' . mvc::config('TPL_RIGHT_DELIMITER') . '/', array('self', '_inside_call_fetchCallBack'), $content);

        /**
         * 模版界定符 还原为 php 语句
         */
        $content = preg_replace('/(' . mvc::config('TPL_LEFT_DELIMITER') . ')\s*\$(.*?);?\s*(' . mvc::config('TPL_RIGHT_DELIMITER') . ')/', '<?php echo \$$2; ?>', $content);
        $content = preg_replace('/(' . mvc::config('TPL_LEFT_DELIMITER') . ')\s*(.*?);?\s*(' . mvc::config('TPL_RIGHT_DELIMITER') . '){1}/', '<?php $2; ?>', $content);

        /**
         * 如果是引用模式，返回当前引用的内容
         */
        if ($isInclude === true) {
            return $content;
        }

        /**
         * 否则释放变量
         */
        $tmpFilePath = tempnam(sys_get_temp_dir(), "mvcTpl_" . date('YmdHIs') . '_');
        $displayVal = isset($this->_displayVal) ? $this->_displayVal : $this->displayVal;

        $content = $this->extract($content, $displayVal, $tmpFilePath);

        return $content;
    }

    /**
     * 模版变量释放函数，单独用一个函数处理，尽量减少变量的符号列表
     * @param string $__content
     * @param array $__displayVal
     * @param string $__tmpFilePath
     * @return mixed|string
     * @throws Exception
     */
    final public function extract($__content = '', $__displayVal = array(), $__tmpFilePath = '')
    {
        $__handle = fopen($__tmpFilePath, "w");

        if (!file_exists($__tmpFilePath) || !$__handle) {
            throw new Exception("模版目录不可写! 请检查: " . $__tmpFilePath, 1);
        }

        fwrite($__handle, $__content);
        fclose($__handle);
        extract($__displayVal);
        ob_start();
        include_once $__tmpFilePath;
        $__content = ob_get_contents();
        ob_end_clean();

        if ($this->layoutName[$this->methodName] != null) {
            $__content = $this->_inside_call_releaseLayout($__content);
        }
        unlink($__tmpFilePath);        // @todo cache

        return $__content;

    }

    /**
     * 展现视图
     *
     * /**
     * @param string $name
     * @param array $displayVal
     * @return mixed|string
     */
    function display($name = '', $displayVal = array())
    {
        if (count($displayVal) > 0) {
            $this->assign($displayVal);
        }
        @header("Content-type:text/html");
        echo $this->fetch($name);
        return true;
    }


    /**
     * 处理外部（浏览器）调用， 其他模块调用时候找不到方法的情况，不允许子类自由实现
     * @param string $name 方
     * @param string $name 方法名
     * @param array $args 参数
     * @return mixed
     * @throws Exception
     */
    final function __call($name = '', $args = array())
    {

        /**
         * 外部调用 _out__call
         */
        if ($name == '_out__call') {

            $route = $args[0];
            $m = $route['m'];
            $a = $route['a'];
            mvc::log('外部调开始');
            /**
             * 禁止调用保护方法
             */
            if (in_array($a, get_class_methods(__CLASS__))) {
                throw new Exception("不允许从外部调用 Action 内的保护方法!", 1);
            }
            /**
             * 进行 filterAccess 验证
             */
            if ($this->filterAccess($a) != true) {
                throw new Exception("filterAccess 验证不通过!", 1);
            }
            /**
             * 已经实现对应的方法，如 indexAction -> index
             */
            if (method_exists($this->className, $a)) {
                mvc::log($a . '方法准备就绪');
                return call_user_func_array(array($this, $a), $args);
            }
            /**
             *  没有方法、尝试直接展现模版
             */
            mvc::log('无法执行: ' . $m . ' -> ' . $a . ' ,尝试展现模板');

            $templateName = mvc::config('DIR_TPL') . '/' . mvc::config('TPL_THEME') . '/' . $route['m'] . '/' . $route['a'] . mvc::config('DEF_TPL_EXT');
            $templateFile = realpath(mvc::config('PATH_APP') . '/' . $templateName);

            mvc::log('模板路径: ' . $templateName);

            if ($templateFile) {
                return $this->display();
            }
            /**
             * 没有对应的方法（一定有文件，否则模块初始化的时候，没有方法又没有模版直接会停止）、没有模版
             */
            throw new Exception('无法执行: ' . $m . ' -> ' . $a . ' ,也没有可以展现的模板: ' . $templateName, 1);
        }

        /**
         * 内部调用 / 内部非 Action 定义的方法调用
         */
        mvc::log('内部不明确的调用开始 ' . $name);

        $route = array(
            'm' => $this->moduleName,
            'a' => $name,
        );

        /**
         * 尝试是否在特殊定义的函数中
         */

        /**
         * 辅助函数 - fetch 中获取模版的最终路径
         */
        if ($name == '_inside_call_makeTemplateFinalPath') {

            mvc::log('_inside_call_makeTemplateFinalPath');

            $pathInfoArr = array(mvc::config('PATH_APP'), mvc::config('DIR_TPL'), mvc::config('TPL_THEME'), '3::MODULE', '4::METHOD.$DEF_TPL_EXT | Filename');
            $pathInfoArr[3] = $this->moduleName;
            $pathInfoArr[4] = $this->methodName . mvc::config('DEF_TPL_EXT');

            if (!is_null($args[0])) {

                $nameSplitArr = explode('/', $args[0]);
                switch (count($nameSplitArr)) {
                    /**
                     * index.php  , 在当前控制器目录下
                     */
                    case 1:
                        $pathInfoArr[4] = $nameSplitArr[0];
                        break;
                    /**
                     * otherAction/index.php , 在其他控制器目录下
                     */
                    case 2:
                        $pathInfoArr[3] = $nameSplitArr[0];
                        $pathInfoArr[4] = $nameSplitArr[1];
                        break;
                    /**
                     * theme/third-party/index.php 考虑主题
                     */
                    case 3:
                        $pathInfoArr[2] = $nameSplitArr[0];
                        $pathInfoArr[3] = $nameSplitArr[1];
                        $pathInfoArr[4] = $nameSplitArr[2];
                        break;
                }
            }
            /**
             * 模板文件路径 - 1
             */
            $pathFinal = realpath(implode('/', $pathInfoArr));
            /**
             * unset 0::PATH_APP
             */
            unset($pathInfoArr[0]);
            /**
             * 共享项目模板文件路径 - 2
             */
            $pathFinalInShareApp = mvc::getShareAppFile('/' . implode('/', $pathInfoArr));
            /**
             * 最终模板文件路径
             */
            $pathFinal = $pathFinal ? $pathFinal : $pathFinalInShareApp;
            return $pathFinal;
        }

        /**
         * 辅助函数 - fetch 中替换模版内容中的路径字符串
         * 替换 href="../Public/" 和 src="../Public/" 的情况，以 http 开头的不替换
         */
        if ($name == '_inside_call_replaceTemplateUrl') {
            $content = $args[0];

            /**
             *  替换 href="../Public/" 和 src="../Public/"  => TPL_URL_PUBLIC
             */
            $content = preg_replace('/(href=")(?!http)(\.\.\/Public\/)([^"]+)(")/', "$1" . mvc::config('TPL_URL_PUBLIC') . "$3 $4", $content);
            $content = preg_replace('/(src=")(?!http)(\.\.\/Public\/)([^"]+)(")/', "$1" . mvc::config('TPL_URL_PUBLIC') . "$3 $4", $content);

            // $content = preg_replace('/(href=")(?!http)(\.\/)([^"]+)(")/', "$1" . mvc::config('TPL_URL_RELATIVE') . "$3 $4", $content);
            // $content = preg_replace('/(src=")(?!http)(\.\/)([^"]+)(")/', "$1" . mvc::config('TPL_URL_RELATIVE') . "$3 $4", $content);

            /**
             *  替换 href="../../" 和 src="../../"  => TPL_URL_ROOT
             */
            $content = preg_replace('/(href=")(?!http)(\.\.\/\.\.\/)([^"]+)(")/', "$1" . mvc::config('TPL_URL_ROOT') . "$3 $4", $content);
            $content = preg_replace('/(src=")(?!http)(\.\.\/\.\.\/)([^"]+)(")/', "$1" . mvc::config('TPL_URL_ROOT') . "$3 $4", $content);
            return $content;
        }

        /**
         * 辅助函数 - mvc::module 中初始化重设模版相关的 URL, eg: TPL_URL_PUBLIC
         */

        if ($name == '_inside_call_setConfigTemplateURL') {

            $pathApp = mvc::config('PATH_APP');
            $pathPublic = realpath(implode('/', array(mvc::config('PATH_APP'), mvc::config('DIR_TPL'), mvc::config('TPL_THEME'), 'Public')));

            $tplUrlRoot = rtrim('//' . $_SERVER['HTTP_HOST'], '/') . dirname($_SERVER['PHP_SELF']);
            $tplUrlPublic = $tplUrlRoot . substr($pathPublic, strlen($pathApp));

            mvc::config('TPL_URL_ROOT', $tplUrlRoot . '/');
            mvc::config('TPL_URL_PUBLIC', $tplUrlPublic . '/');

            return '';
        }

        /**
         * 辅助函数 fetch 递归处理引用
         */
        if ($name == '_inside_call_fetchCallBack') {
            return $this->fetch(trim($args[0][1]), true);
        }

        /**
         * 辅助函数 fetch 处理模板设置了 layout 的情况
         */
        if ($name == '_inside_call_releaseLayout') {
            $content = $args[0];
            $name = $this->layoutName[$this->methodName];
            $this->layoutName[$this->methodName] = null;
            $content = $this->fetch($name, array('content' => $content));
            return $content;
        }

        /**
         * 尝试展现模版
         */
        $templateName = mvc::config('DIR_TPL') . '/' . mvc::config('TPL_THEME') . '/' . $route['m'] . '/' . $route['a'] . mvc::config('DEF_TPL_EXT');
        $templateFile = realpath(mvc::config('PATH_APP') . '/' . $templateName);

        mvc::log('模板路径: ' . $templateName);

        if ($templateFile) {
            return $this->display();
        }
        throw new Exception('内部调用无法执行: ' . $route['m'] . ' -> ' . $route['a'] . ' ,也没有可以展现的模板: ' . $templateName, 1);
    }
}
