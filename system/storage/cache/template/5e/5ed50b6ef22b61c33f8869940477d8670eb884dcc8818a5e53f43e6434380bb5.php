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

/* tailwind/template/common/footer.twig */
class __TwigTemplate_e9dc5e7d129369ec871ef0503e53941613120b97e4468a48e8080cafda893ee5 extends \Twig\Template
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
        echo "<footer class=\"footer\">
    <div class=\"footer-container\">
        ";
        // line 3
        if (($context["informations"] ?? null)) {
            // line 4
            echo "            <div>
                <p class=\"footer-title\">
                    ";
            // line 6
            echo ($context["text_information"] ?? null);
            echo "
                </p>
                ";
            // line 8
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["informations"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["information"]) {
                // line 9
                echo "                    <a href=\"";
                echo twig_get_attribute($this->env, $this->source, $context["information"], "href", [], "any", false, false, false, 9);
                echo "\" class=\"footer-link\">";
                echo twig_get_attribute($this->env, $this->source, $context["information"], "title", [], "any", false, false, false, 9);
                echo "</a>
                ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['information'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 11
            echo "            </div>
        ";
        }
        // line 13
        echo "
        <div>
            <p class=\"footer-title\">
                ";
        // line 16
        echo ($context["text_service"] ?? null);
        echo "
            </p>
            <a href=\"";
        // line 18
        echo ($context["contact"] ?? null);
        echo "\" class=\"footer-link\">";
        echo ($context["text_contact"] ?? null);
        echo "</a>
            <a href=\"";
        // line 19
        echo ($context["return"] ?? null);
        echo "\" class=\"footer-link\">";
        echo ($context["text_return"] ?? null);
        echo "</a>
            <a href=\"";
        // line 20
        echo ($context["sitemap"] ?? null);
        echo "\" class=\"footer-link\">";
        echo ($context["text_sitemap"] ?? null);
        echo "</a>
        </div>

        <div>
            <p class=\"footer-title\">
                ";
        // line 25
        echo ($context["text_extra"] ?? null);
        echo "
            </p>
            <a href=\"";
        // line 27
        echo ($context["manufacturer"] ?? null);
        echo "\" class=\"footer-link\">";
        echo ($context["text_manufacturer"] ?? null);
        echo "</a>
            <a href=\"";
        // line 28
        echo ($context["voucher"] ?? null);
        echo "\" class=\"footer-link\">";
        echo ($context["text_voucher"] ?? null);
        echo "</a>
            <a href=\"";
        // line 29
        echo ($context["affiliate"] ?? null);
        echo "\" class=\"footer-link\">";
        echo ($context["text_affiliate"] ?? null);
        echo "</a>
            <a href=\"";
        // line 30
        echo ($context["special"] ?? null);
        echo "\" class=\"footer-link\">";
        echo ($context["text_special"] ?? null);
        echo "</a>
        </div>

        <div>
            <p class=\"footer-title\">
                ";
        // line 35
        echo ($context["text_account"] ?? null);
        echo "
            </p>
            <a href=\"";
        // line 37
        echo ($context["account"] ?? null);
        echo "\" class=\"footer-link\">";
        echo ($context["text_account"] ?? null);
        echo "</a>
            <a href=\"";
        // line 38
        echo ($context["order"] ?? null);
        echo "\" class=\"footer-link\">";
        echo ($context["text_order"] ?? null);
        echo "</a>
            <a href=\"";
        // line 39
        echo ($context["wishlist"] ?? null);
        echo "\" class=\"footer-link\">";
        echo ($context["text_wishlist"] ?? null);
        echo "</a>
            <a href=\"";
        // line 40
        echo ($context["newsletter"] ?? null);
        echo "\" class=\"footer-link\">";
        echo ($context["text_newsletter"] ?? null);
        echo "</a>
        </div>
    </div>
    <hr class=\"divider my-4\" />
    <div class=\"footer-container\">
        <p class=\"text-sm text-neutral-600 dark:text-neutral-300\">
            ";
        // line 46
        echo ($context["powered"] ?? null);
        echo "
        </p>
    </div>
</footer>

";
        // line 51
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["styles"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["style"]) {
            // line 52
            echo "    <link href=\"";
            echo twig_get_attribute($this->env, $this->source, $context["style"], "href", [], "any", false, false, false, 52);
            echo "\" type=\"text/css\" rel=\"";
            echo twig_get_attribute($this->env, $this->source, $context["style"], "rel", [], "any", false, false, false, 52);
            echo "\" media=\"";
            echo twig_get_attribute($this->env, $this->source, $context["style"], "media", [], "any", false, false, false, 52);
            echo "\" />
";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['style'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 54
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["scripts"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["script"]) {
            // line 55
            echo "    <script src=\"";
            echo $context["script"];
            echo "\" type=\"text/javascript\"></script>
";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['script'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 57
        echo "<script
    src=\"https://code.jquery.com/jquery-3.7.1.min.js\"
    integrity=\"sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=\"
    crossorigin=\"anonymous\"
></script>
<script src=\"catalog/view/theme/tailwind/javascript/script.js\" type=\"text/javascript\"></script>
";
    }

    public function getTemplateName()
    {
        return "tailwind/template/common/footer.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  208 => 57,  199 => 55,  195 => 54,  182 => 52,  178 => 51,  170 => 46,  159 => 40,  153 => 39,  147 => 38,  141 => 37,  136 => 35,  126 => 30,  120 => 29,  114 => 28,  108 => 27,  103 => 25,  93 => 20,  87 => 19,  81 => 18,  76 => 16,  71 => 13,  67 => 11,  56 => 9,  52 => 8,  47 => 6,  43 => 4,  41 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "tailwind/template/common/footer.twig", "");
    }
}
