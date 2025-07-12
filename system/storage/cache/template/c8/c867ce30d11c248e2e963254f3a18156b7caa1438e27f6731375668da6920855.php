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
class __TwigTemplate_4e56880251b29ade69f73c58c307c155755adb23d6e6bd6650a1f05e47005de6 extends \Twig\Template
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
        echo "<!DOCTYPE html>
<html dir=\"";
        // line 2
        echo ($context["direction"] ?? null);
        echo "\" lang=\"";
        echo ($context["lang"] ?? null);
        echo "\">
    <head>
        <meta charset=\"UTF-8\" />
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" />
        <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" />
        <title>
            ";
        // line 8
        echo ($context["title"] ?? null);
        echo "
        </title>
        ";
        // line 10
        if (($context["robots"] ?? null)) {
            // line 11
            echo "            <meta name=\"robots\" content=\"";
            echo ($context["robots"] ?? null);
            echo "\" />
        ";
        }
        // line 13
        echo "        <base href=\"";
        echo ($context["base"] ?? null);
        echo "\" />
        ";
        // line 14
        if (($context["description"] ?? null)) {
            // line 15
            echo "            <meta name=\"description\" content=\"";
            echo ($context["description"] ?? null);
            echo "\" />
        ";
        }
        // line 17
        echo "        ";
        if (($context["keywords"] ?? null)) {
            // line 18
            echo "            <meta name=\"keywords\" content=\"";
            echo ($context["keywords"] ?? null);
            echo "\" />
        ";
        }
        // line 20
        echo "        <meta property=\"og:title\" content=\"";
        echo ($context["title"] ?? null);
        echo "\" />
        <meta property=\"og:type\" content=\"website\" />
        <meta property=\"og:url\" content=\"";
        // line 22
        echo ($context["og_url"] ?? null);
        echo "\" />
        ";
        // line 23
        if (($context["og_image"] ?? null)) {
            // line 24
            echo "            <meta property=\"og:image\" content=\"";
            echo ($context["og_image"] ?? null);
            echo "\" />
        ";
        } else {
            // line 26
            echo "            <meta property=\"og:image\" content=\"";
            echo ($context["logo"] ?? null);
            echo "\" />
        ";
        }
        // line 28
        echo "        <meta property=\"og:site_name\" content=\"";
        echo ($context["name"] ?? null);
        echo "\" />
        <link href=\"catalog/view/theme/tailwind/stylesheet/tailwind.css\" rel=\"stylesheet\" />
        ";
        // line 30
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["styles"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["style"]) {
            // line 31
            echo "            <link href=\"";
            echo twig_get_attribute($this->env, $this->source, $context["style"], "href", [], "any", false, false, false, 31);
            echo "\" type=\"text/css\" rel=\"";
            echo twig_get_attribute($this->env, $this->source, $context["style"], "rel", [], "any", false, false, false, 31);
            echo "\" media=\"";
            echo twig_get_attribute($this->env, $this->source, $context["style"], "media", [], "any", false, false, false, 31);
            echo "\" />
        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['style'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 33
        echo "        ";
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["scripts"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["script"]) {
            // line 34
            echo "            <script src=\"";
            echo $context["script"];
            echo "\" type=\"text/javascript\"></script>
        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['script'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 36
        echo "        <script src=\"catalog/view/javascript/common.js\" type=\"text/javascript\"></script>
        ";
        // line 37
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["links"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["link"]) {
            // line 38
            echo "            <link href=\"";
            echo twig_get_attribute($this->env, $this->source, $context["link"], "href", [], "any", false, false, false, 38);
            echo "\" rel=\"";
            echo twig_get_attribute($this->env, $this->source, $context["link"], "rel", [], "any", false, false, false, 38);
            echo "\" />
        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['link'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 40
        echo "        ";
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["analytics"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["analytic"]) {
            // line 41
            echo "            ";
            echo $context["analytic"];
            echo "
        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['analytic'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 43
        echo "    </head>
    <body>
        <nav id=\"top\">
            <div class=\"container\">
                ";
        // line 47
        echo ($context["currency"] ?? null);
        echo "
                ";
        // line 48
        echo ($context["language"] ?? null);
        echo "
                ";
        // line 49
        echo ($context["blog_menu"] ?? null);
        echo "
                <div id=\"top-links\" class=\"nav pull-right\">
                    <ul class=\"list-inline\">
                        <li>
                            <a href=\"";
        // line 53
        echo ($context["contact"] ?? null);
        echo "\"><i class=\"fa fa-phone\"></i></a>
                            <span class=\"hidden-xs hidden-sm hidden-md\">";
        // line 54
        echo ($context["telephone"] ?? null);
        echo "</span>
                        </li>
                        <li class=\"dropdown\">
                            <a
                                href=\"";
        // line 58
        echo ($context["account"] ?? null);
        echo "\"
                                title=\"";
        // line 59
        echo ($context["text_account"] ?? null);
        echo "\"
                                class=\"dropdown-toggle\"
                                data-toggle=\"dropdown\"
                            >
                                <i class=\"fa fa-user\"></i>
                                <span class=\"hidden-xs hidden-sm hidden-md\">";
        // line 64
        echo ($context["text_account"] ?? null);
        echo "</span>
                                <span class=\"caret\"></span>
                            </a>
                            <ul class=\"dropdown-menu dropdown-menu-right\">
                                ";
        // line 68
        if (($context["logged"] ?? null)) {
            // line 69
            echo "                                    <li><a href=\"";
            echo ($context["account"] ?? null);
            echo "\">";
            echo ($context["text_account"] ?? null);
            echo "</a></li>
                                    <li><a href=\"";
            // line 70
            echo ($context["order"] ?? null);
            echo "\">";
            echo ($context["text_order"] ?? null);
            echo "</a></li>
                                    <li><a href=\"";
            // line 71
            echo ($context["transaction"] ?? null);
            echo "\">";
            echo ($context["text_transaction"] ?? null);
            echo "</a></li>
                                    <li><a href=\"";
            // line 72
            echo ($context["download"] ?? null);
            echo "\">";
            echo ($context["text_download"] ?? null);
            echo "</a></li>
                                    <li><a href=\"";
            // line 73
            echo ($context["logout"] ?? null);
            echo "\">";
            echo ($context["text_logout"] ?? null);
            echo "</a></li>
                                ";
        } else {
            // line 75
            echo "                                    <li><a href=\"";
            echo ($context["register"] ?? null);
            echo "\">";
            echo ($context["text_register"] ?? null);
            echo "</a></li>
                                    <li><a href=\"";
            // line 76
            echo ($context["login"] ?? null);
            echo "\">";
            echo ($context["text_login"] ?? null);
            echo "</a></li>
                                ";
        }
        // line 78
        echo "                            </ul>
                        </li>
                        <li>
                            <a href=\"";
        // line 81
        echo ($context["wishlist"] ?? null);
        echo "\" id=\"wishlist-total\" title=\"";
        echo ($context["text_wishlist"] ?? null);
        echo "\">
                                <i class=\"fa fa-heart\"></i>
                                <span class=\"hidden-xs hidden-sm hidden-md\">";
        // line 83
        echo ($context["text_wishlist"] ?? null);
        echo "</span>
                            </a>
                        </li>
                        <li>
                            <a href=\"";
        // line 87
        echo ($context["shopping_cart"] ?? null);
        echo "\" title=\"";
        echo ($context["text_shopping_cart"] ?? null);
        echo "\">
                                <i class=\"fa fa-shopping-cart\"></i>
                                <span class=\"hidden-xs hidden-sm hidden-md\">";
        // line 89
        echo ($context["text_shopping_cart"] ?? null);
        echo "</span>
                            </a>
                        </li>
                        <li>
                            <a href=\"";
        // line 93
        echo ($context["checkout"] ?? null);
        echo "\" title=\"";
        echo ($context["text_checkout"] ?? null);
        echo "\">
                                <i class=\"fa fa-share\"></i>
                                <span class=\"hidden-xs hidden-sm hidden-md\">";
        // line 95
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
        // line 107
        if (($context["logo"] ?? null)) {
            // line 108
            echo "                                ";
            if ((($context["home"] ?? null) == ($context["og_url"] ?? null))) {
                // line 109
                echo "                                    <img src=\"";
                echo ($context["logo"] ?? null);
                echo "\" title=\"";
                echo ($context["name"] ?? null);
                echo "\" alt=\"";
                echo ($context["name"] ?? null);
                echo "\" class=\"img-responsive\" />
                                ";
            } else {
                // line 111
                echo "                                    <a href=\"";
                echo ($context["home"] ?? null);
                echo "\">
                                        <img
                                            src=\"";
                // line 113
                echo ($context["logo"] ?? null);
                echo "\"
                                            title=\"";
                // line 114
                echo ($context["name"] ?? null);
                echo "\"
                                            alt=\"";
                // line 115
                echo ($context["name"] ?? null);
                echo "\"
                                            class=\"img-responsive\"
                                        />
                                    </a>
                                ";
            }
            // line 120
            echo "                            ";
        } else {
            // line 121
            echo "                                <h1><a href=\"";
            echo ($context["home"] ?? null);
            echo "\">";
            echo ($context["name"] ?? null);
            echo "</a></h1>
                            ";
        }
        // line 123
        echo "                        </div>
                    </div>
                    <div class=\"col-sm-5\">
                        ";
        // line 126
        echo ($context["search"] ?? null);
        echo "
                    </div>
                    <div class=\"col-sm-3\">
                        ";
        // line 129
        echo ($context["cart"] ?? null);
        echo "
                    </div>
                </div>
            </div>
        </header>
        ";
        // line 134
        echo ($context["menu"] ?? null);
        echo "
    </body>
</html>
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
        return array (  400 => 134,  392 => 129,  386 => 126,  381 => 123,  373 => 121,  370 => 120,  362 => 115,  358 => 114,  354 => 113,  348 => 111,  338 => 109,  335 => 108,  333 => 107,  318 => 95,  311 => 93,  304 => 89,  297 => 87,  290 => 83,  283 => 81,  278 => 78,  271 => 76,  264 => 75,  257 => 73,  251 => 72,  245 => 71,  239 => 70,  232 => 69,  230 => 68,  223 => 64,  215 => 59,  211 => 58,  204 => 54,  200 => 53,  193 => 49,  189 => 48,  185 => 47,  179 => 43,  170 => 41,  165 => 40,  154 => 38,  150 => 37,  147 => 36,  138 => 34,  133 => 33,  120 => 31,  116 => 30,  110 => 28,  104 => 26,  98 => 24,  96 => 23,  92 => 22,  86 => 20,  80 => 18,  77 => 17,  71 => 15,  69 => 14,  64 => 13,  58 => 11,  56 => 10,  51 => 8,  40 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "tailwind/template/common/header.twig", "");
    }
}
