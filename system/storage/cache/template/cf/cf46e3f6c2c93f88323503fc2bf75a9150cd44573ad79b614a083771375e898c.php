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

/* tailwind/template/common/cart.twig */
class __TwigTemplate_be7e454b2bda9551b45279c835fadd0be16a270902f448a81c41325e2168c7b7 extends \Twig\Template
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
        echo "<button class=\"button-primary icon relative\" id=\"cart\" onclick=\"openCart()\">
    <svg aria-hidden=\"true\" viewBox=\"0 0 24 24\">
        <use href=\"/assets/icons/sprite.svg#icon-cart\"></use>
    </svg>
    <span id=\"cart-badge\" class=\"badge button-badge\">";
        // line 5
        echo ($context["productCount"] ?? null);
        echo "</span>
    <div class=\"cart-toast\" id=\"cart-toast\"></div>
</button>

<div class=\"modal flex flex-col max-h-md h-full hidden p-4 md:p-6 max-md:rounded-none lg:max-h-[80vh]\" id=\"cart-modal\">
    <div class=\"skeleton h-full w-full\"></div>
</div>
<div class=\"modal-overlay hidden\" id=\"cart-overlay\" onClick=\"closeCart()\"></div>

";
    }

    public function getTemplateName()
    {
        return "tailwind/template/common/cart.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  43 => 5,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "tailwind/template/common/cart.twig", "");
    }
}
