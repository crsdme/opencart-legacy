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
class __TwigTemplate_eeaaff281cbb475fc567f2efe062219e43f085f959ed59dbfc376672c2f2b57a extends \Twig\Template
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
        echo "<header class=\"header\">
    <div class=\"flex flex-wrap items-center gap-2\">
        <button class=\"button-secondary icon\" onclick=\"openMenu()\">
            <svg aria-hidden=\"true\" viewBox=\"0 0 24 24\">
                <use href=\"/assets/icons/sprite.svg#icon-menu\"></use>
            </svg>
        </button>
    </div>
    ";
        // line 9
        if (("common/home" == ($context["route"] ?? null))) {
            // line 10
            echo "        <img src=\"";
            echo ($context["logo"] ?? null);
            echo "\" title=\"";
            echo ($context["name"] ?? null);
            echo "\" alt=\"";
            echo ($context["name"] ?? null);
            echo "\" />
    ";
        } else {
            // line 12
            echo "        <a href=\"";
            echo ($context["home"] ?? null);
            echo "\"><img src=\"";
            echo ($context["logo"] ?? null);
            echo "\" title=\"";
            echo ($context["name"] ?? null);
            echo "\" alt=\"";
            echo ($context["name"] ?? null);
            echo "\" /></a>
    ";
        }
        // line 14
        echo "    <div class=\"flex flex-wrap items-center gap-2\">
        ";
        // line 15
        echo ($context["currency"] ?? null);
        echo "
        ";
        // line 16
        echo ($context["language"] ?? null);
        echo "
        ";
        // line 17
        echo ($context["search"] ?? null);
        echo "
        <button class=\"button-secondary icon\" onclick=\"toggleTheme()\">
            <svg aria-hidden=\"true\" viewBox=\"0 0 24 24\">
                <use id=\"theme-icon\" href=\"";
        // line 20
        echo ($context["icons"] ?? null);
        echo "#icon-sun\"></use>
            </svg>
        </button>
        <a href=\"";
        // line 23
        echo ($context["wishlist"] ?? null);
        echo "\" class=\"button-secondary icon\">
            <svg aria-hidden=\"true\" viewBox=\"0 0 24 24\">
                <use href=\"/assets/icons/sprite.svg#icon-heart\"></use>
            </svg>
        </a>
        <a href=\"";
        // line 28
        echo ($context["account"] ?? null);
        echo "\" class=\"button-secondary icon\">
            <svg aria-hidden=\"true\" viewBox=\"0 0 24 24\">
                <use href=\"/assets/icons/sprite.svg#icon-user\"></use>
            </svg>
        </a>
        <div class=\"dropdown w-full flex-1\" data-align=\"right\">
            <button id=\"dropdownButton\" class=\"button-secondary icon\">
                <svg aria-hidden=\"true\" viewBox=\"0 0 24 24\">
                    <use href=\"/assets/icons/sprite.svg#icon-phone\"></use>
                </svg>
            </button>

            <div id=\"dropdownMenu\" class=\"dropdown-menu\">
                <a class=\"dropdown-item\" href=\"tel:+380668875507\">+38 (123) 456 7890</a>
                <a class=\"dropdown-item\" href=\"mailto:info@example.com\">info@example.com</a>
                <a class=\"dropdown-item\" href=\"https://wa.me/905314918688\">+90 531 491 8688</a>
            </div>
        </div>
        ";
        // line 46
        echo ($context["cart"] ?? null);
        echo "
    </div>
</header>
";
        // line 49
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
        return array (  129 => 49,  123 => 46,  102 => 28,  94 => 23,  88 => 20,  82 => 17,  78 => 16,  74 => 15,  71 => 14,  59 => 12,  49 => 10,  47 => 9,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "tailwind/template/common/header.twig", "");
    }
}
