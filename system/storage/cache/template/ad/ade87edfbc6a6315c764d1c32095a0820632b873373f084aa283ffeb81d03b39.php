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

/* tailwind/template/common/menu.twig */
class __TwigTemplate_a0604563960401edd8bea2ac7443cd2dd805795778580032815f93142aa61cc3 extends \Twig\Template
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
        echo "<div class=\"sheet p-4\" data-side=\"left\" id=\"menu-sheet\">
      <div class=\"sheet-header mb-2\">
        <p>";
        // line 3
        echo ($context["text_category"] ?? null);
        echo "</p>
        <button class=\"button-secondary icon\" onclick=\"closeMenu()\">
          <svg aria-hidden=\"true\" viewBox=\"0 0 24 24\">
            <use href=\"/assets/icons/sprite.svg#icon-close\"></use> 
          </svg>
        </button>
      </div>
      <hr class=\"divider\" />
      <div class=\"sheet-content\">
        <nav class=\"flex flex-col gap-2 mt-2\">
            ";
        // line 13
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["categories"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["category"]) {
            // line 14
            echo "                <a href=\"";
            echo twig_get_attribute($this->env, $this->source, $context["category"], "href", [], "any", false, false, false, 14);
            echo "\" class=\"menu-link\">";
            echo twig_get_attribute($this->env, $this->source, $context["category"], "name", [], "any", false, false, false, 14);
            echo "</a>
                <hr class=\"divider\" />
            ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['category'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 17
        echo "        </nav>
      </div>
    </div>
    <div class='sheet-overlay hidden' id=\"sheet-overlay\" onClick='closeMenu()'></div>";
    }

    public function getTemplateName()
    {
        return "tailwind/template/common/menu.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  70 => 17,  58 => 14,  54 => 13,  41 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "tailwind/template/common/menu.twig", "");
    }
}
