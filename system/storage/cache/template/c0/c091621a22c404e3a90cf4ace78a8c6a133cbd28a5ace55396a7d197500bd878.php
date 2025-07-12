<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* tailwind/template/common/header.twig */
class __TwigTemplate_cd2a40b90af4aca55a72006d7a3cb362b3d79ffb0c00808aa332c096a7a9b17b extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo "<nav id=\"top\">
    <div class=\"container\">
        ";
        // line 3
        echo ($context["currency"] ?? null);
        echo "
        ";
        // line 4
        echo ($context["language"] ?? null);
        echo "
        ";
        // line 5
        echo ($context["blog_menu"] ?? null);
        echo "
        <div id=\"top-links\" class=\"nav pull-right\">
            <ul class=\"list-inline\">
                <li>
                    <a href=\"";
        // line 9
        echo ($context["contact"] ?? null);
        echo "\"><i class=\"fa fa-phone\"></i></a>
                    <span class=\"hidden-xs hidden-sm hidden-md\">";
        // line 10
        echo ($context["telephone"] ?? null);
        echo "</span>
                </li>
                <li class=\"dropdown\">
                    <a href=\"";
        // line 13
        echo ($context["account"] ?? null);
        echo "\" title=\"";
        echo ($context["text_account"] ?? null);
        echo "\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">
                        <i class=\"fa fa-user\"></i>
                        <span class=\"hidden-xs hidden-sm hidden-md\">";
        // line 15
        echo ($context["text_account"] ?? null);
        echo "</span>
                        <span class=\"caret\"></span>
                    </a>
                    <ul class=\"dropdown-menu dropdown-menu-right\">
                        ";
        // line 19
        if (($context["logged"] ?? null)) {
            // line 20
            echo "                            <li><a href=\"";
            echo ($context["account"] ?? null);
            echo "\">";
            echo ($context["text_account"] ?? null);
            echo "</a></li>
                            <li><a href=\"";
            // line 21
            echo ($context["order"] ?? null);
            echo "\">";
            echo ($context["text_order"] ?? null);
            echo "</a></li>
                            <li><a href=\"";
            // line 22
            echo ($context["transaction"] ?? null);
            echo "\">";
            echo ($context["text_transaction"] ?? null);
            echo "</a></li>
                            <li><a href=\"";
            // line 23
            echo ($context["download"] ?? null);
            echo "\">";
            echo ($context["text_download"] ?? null);
            echo "</a></li>
                            <li><a href=\"";
            // line 24
            echo ($context["logout"] ?? null);
            echo "\">";
            echo ($context["text_logout"] ?? null);
            echo "</a></li>
                        ";
        } else {
            // line 26
            echo "                            <li><a href=\"";
            echo ($context["register"] ?? null);
            echo "\">";
            echo ($context["text_register"] ?? null);
            echo "</a></li>
                            <li><a href=\"";
            // line 27
            echo ($context["login"] ?? null);
            echo "\">";
            echo ($context["text_login"] ?? null);
            echo "</a></li>
                        ";
        }
        // line 29
        echo "                    </ul>
                </li>
                <li>
                    <a href=\"";
        // line 32
        echo ($context["wishlist"] ?? null);
        echo "\" id=\"wishlist-total\" title=\"";
        echo ($context["text_wishlist"] ?? null);
        echo "\">
                        <i class=\"fa fa-heart\"></i>
                        <span class=\"hidden-xs hidden-sm hidden-md\">";
        // line 34
        echo ($context["text_wishlist"] ?? null);
        echo "</span>
                    </a>
                </li>
                <li>
                    <a href=\"";
        // line 38
        echo ($context["shopping_cart"] ?? null);
        echo "\" title=\"";
        echo ($context["text_shopping_cart"] ?? null);
        echo "\">
                        <i class=\"fa fa-shopping-cart\"></i>
                        <span class=\"hidden-xs hidden-sm hidden-md\">";
        // line 40
        echo ($context["text_shopping_cart"] ?? null);
        echo "</span>
                    </a>
                </li>
                <li>
                    <a href=\"";
        // line 44
        echo ($context["checkout"] ?? null);
        echo "\" title=\"";
        echo ($context["text_checkout"] ?? null);
        echo "\">
                        <i class=\"fa fa-share\"></i>
                        <span class=\"hidden-xs hidden-sm hidden-md\">";
        // line 46
        echo ($context["text_checkout"] ?? null);
        echo "</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<header>
    <div class=\"container\">
        <div class=\"row\">
            <div class=\"col-sm-4\">
                <div id=\"logo\">
                    ";
        // line 58
        if (($context["logo"] ?? null)) {
            // line 59
            echo "                        ";
            if ((($context["home"] ?? null) == ($context["og_url"] ?? null))) {
                // line 60
                echo "                            <img src=\"";
                echo ($context["logo"] ?? null);
                echo "\" title=\"";
                echo ($context["name"] ?? null);
                echo "\" alt=\"";
                echo ($context["name"] ?? null);
                echo "\" class=\"img-responsive\" />
                        ";
            } else {
                // line 62
                echo "                            <a href=\"";
                echo ($context["home"] ?? null);
                echo "\">
                                <img src=\"";
                // line 63
                echo ($context["logo"] ?? null);
                echo "\" title=\"";
                echo ($context["name"] ?? null);
                echo "\" alt=\"";
                echo ($context["name"] ?? null);
                echo "\" class=\"img-responsive\" />
                            </a>
                        ";
            }
            // line 66
            echo "                    ";
        } else {
            // line 67
            echo "                        <h1><a href=\"";
            echo ($context["home"] ?? null);
            echo "\">";
            echo ($context["name"] ?? null);
            echo "</a></h1>
                    ";
        }
        // line 69
        echo "                </div>
            </div>
            <div class=\"col-sm-5\">
                ";
        // line 72
        echo ($context["search"] ?? null);
        echo "
            </div>
            <div class=\"col-sm-3\">
                ";
        // line 75
        echo ($context["cart"] ?? null);
        echo "
            </div>
        </div>
    </div>
</header>
";
        // line 80
        echo ($context["menu"] ?? null);
        echo "
";
    }

    public function getTemplateName()
    {
        return "tailwind/template/common/header.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  243 => 80,  235 => 75,  229 => 72,  224 => 69,  216 => 67,  213 => 66,  203 => 63,  198 => 62,  188 => 60,  185 => 59,  183 => 58,  168 => 46,  161 => 44,  154 => 40,  147 => 38,  140 => 34,  133 => 32,  128 => 29,  121 => 27,  114 => 26,  107 => 24,  101 => 23,  95 => 22,  89 => 21,  82 => 20,  80 => 19,  73 => 15,  66 => 13,  60 => 10,  56 => 9,  49 => 5,  45 => 4,  41 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "tailwind/template/common/header.twig", "/var/www/html/catalog/view/theme/tailwind/template/common/header.twig");
    }
}
