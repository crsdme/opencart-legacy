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

/* tailwind/template/common/components.twig */
class __TwigTemplate_ba7c245fcfa18a9c6d33c07d6628712f8c671fe798824e94cb97cce32ff15c83 extends \Twig\Template
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
<html lang=\"en\">
    <head>
        <meta charset=\"UTF-8\" />
        <title>
            Tailwind UI Components
        </title>
        <link rel=\"stylesheet\" href=\"/catalog/view/theme/tailwind/stylesheet/tailwind.css\" />
    </head>
    <body class=\"p-8 bg-white text-black space-y-12\">
        <h1 class=\"text-4xl font-bold\">📦 UI Components Showcase</h1>

        ";
        // line 13
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["components"] ?? null));
        $context['loop'] = [
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        ];
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["_key"] => $context["component"]) {
            // line 14
            echo "            <section class=\"border-t border-gray-300 pt-6\">
                <h2 class=\"text-2xl font-semibold mb-4\">";
            // line 15
            echo $context["component"];
            echo "</h2>
                ";
            // line 16
            $this->loadTemplate((("theme/tailwind/components/" . $context["component"]) . ".twig"), "tailwind/template/common/components.twig", 16)->display($context);
            // line 17
            echo "            </section>
        ";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['component'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 19
        echo "    </body>
</html>
";
    }

    public function getTemplateName()
    {
        return "tailwind/template/common/components.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  92 => 19,  77 => 17,  75 => 16,  71 => 15,  68 => 14,  51 => 13,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "tailwind/template/common/components.twig", "");
    }
}
