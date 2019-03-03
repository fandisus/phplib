<?php
namespace Trust;
class BsNavLink {
    public $text;
    public $href;
    public $children;
    public $isRight;
    public static $level=0;
    public function __construct($text, $href, $children, $isRight=false) {
        $this->text = $text;
        $this->href=$href;
        $this->children=$children;
        $this->isRight=$isRight;
    }
    public function add($child) {$this->children[] = $child;}
    public function render() {
        navlink::$level++;
//        echo "<li><a href=\"$this->href\">$this->text</a>\n";
//        if (isset($this->children) && is_array($this->children)) {
//            echo "<ul>\n";
//            foreach ($this->children as $child) $child->render();
//            echo "</ul>\n";
//        }
//        echo "</li>\n";
        $hasChild = (isset($this->children) && is_array($this->children));
        if (!$hasChild) {
            echo "<li class=\"menu-item\"><a href=\"$this->href\">$this->text</a></li>\n";
        } else {
            if (navlink::$level == 1){
                echo "<li class=\"menu-item dropdown\"><a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">$this->text <span class=\"caret\"></span></a>";
            } else {
                echo "<li class=\"menu-item dropdown dropdown-submenu\"><a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">$this->text</a>";
            }
            echo "<ul class=\"dropdown-menu\">";
            foreach ($this->children as $child) $child->render();
            echo "</ul></li>";
        }
        navlink::$level--;
    }
    public function topRender($id, $href) {
        ?>
<div class="navbar-xs"><div class="navbar-primary">
        <nav class="navbar navbar-default" role="navigation">
            <div class="container-fluid">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#<?php echo $id;?>">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="<?php echo $href; ?>"><?php echo $id; ?></a>
                </div>
                <div class="collapse navbar-collapse" id="<?php echo $id; ?>">
                    <ul class="nav navbar-nav">
<?php foreach($this->children as $child) { if(!$child->isRight) $child->render(); } ?>
                    </ul>
                    <ul class="nav navbar-nav navbar-right">
<?php foreach($this->children as $child) { if($child->isRight) $child->render(); } ?>
                    </ul>
                </div>
            </div>
        </nav>
</div></div>        
        <?php
    }
}
