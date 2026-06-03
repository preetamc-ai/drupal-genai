<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* modules/contrib/tmgmt/templates/tmgmt-data-items-form.html.twig */
class __TwigTemplate_a5bf696b0535b427e0c72c59184e4f28 extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 14
        yield "
<div id=\"";
        // line 15
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["ajaxid"] ?? null), "html", null, true);
        yield "\">

  <table class=\"tmgmt-ui-review\">
    <colgroup width=\"100\"/>
    <colgroup width=\"*\" span=\"2\"/>
    <colgroup width=\"100\"/>

    <thead>
    <tr>
      <th colspan=\"4\">";
        // line 24
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["top_label"] ?? null), "html", null, true);
        yield "</th>
    </tr>
    </thead>

    ";
        // line 28
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(($context["element"] ?? null));
        foreach ($context['_seq'] as $context["group_key"] => $context["group"]) {
            // line 29
            yield "      ";
            if ((Twig\Extension\CoreExtension::first($this->env->getCharset(), $context["group_key"]) != "#")) {
                // line 30
                yield "        <tbody>
        ";
                // line 31
                if ((($tmp = (($_v0 = $context["group"]) && is_array($_v0) || $_v0 instanceof ArrayAccess && in_array($_v0::class, CoreExtension::ARRAY_LIKE_CLASSES, true) ? ($_v0["#group_label"] ?? null) : CoreExtension::getAttribute($this->env, $this->source, $context["group"], "#group_label", [], "array", false, false, true, 31))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 32
                    yield "          <tr>
            <th colspan=\"4\">";
                    // line 33
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::join((($_v1 = $context["group"]) && is_array($_v1) || $_v1 instanceof ArrayAccess && in_array($_v1::class, CoreExtension::ARRAY_LIKE_CLASSES, true) ? ($_v1["#group_label"] ?? null) : CoreExtension::getAttribute($this->env, $this->source, $context["group"], "#group_label", [], "array", false, false, true, 33)), " > "), "html", null, true);
                    yield "</th>
          </tr>
        ";
                }
                // line 36
                yield "
        ";
                // line 37
                $context['_parent'] = $context;
                $context['_seq'] = CoreExtension::ensureTraversable($context["group"]);
                foreach ($context['_seq'] as $context["key"] => $context["item"]) {
                    // line 38
                    yield "          ";
                    if ((Twig\Extension\CoreExtension::first($this->env->getCharset(), $context["key"]) != "#")) {
                        // line 39
                        yield "            <tr>
              <td class=\"tmgmt-ui-data-item-label\">
                <div class=\"form-item form-type-label\">
                  <label>";
                        // line 42
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "label", [], "any", false, false, true, 42), "html", null, true);
                        yield "</label>
                </div>
                <div class=\"tmgmt-ui-state\">
                  ";
                        // line 45
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "status", [], "any", false, false, true, 45), "html", null, true);
                        yield "
                </div>
              </td>
              <td class=\"tmgmt-ui-data-item-source\">
                ";
                        // line 49
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "source", [], "any", false, false, true, 49), "html", null, true);
                        yield "
              </td>
              <td class=\"tmgmt-ui-data-item-translation\">
                ";
                        // line 52
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "translation", [], "any", false, false, true, 52), "html", null, true);
                        yield "
              </td>
              <td class=\"tmgmt-ui-data-item-actions\">
                ";
                        // line 55
                        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "actions", [], "any", false, false, true, 55), "html", null, true);
                        yield "
              </td>
            </tr>

            ";
                        // line 59
                        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["item"], "below", [], "any", false, false, true, 59)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                            // line 60
                            yield "              <tr>
                <td></td>
                <td colspan=\"2\"> ";
                            // line 62
                            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "below", [], "any", false, false, true, 62), "html", null, true);
                            yield "</td>
                <td>";
                            // line 63
                            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "below_actions", [], "any", false, false, true, 63), "html", null, true);
                            yield "</td>
              </tr>
            ";
                        }
                        // line 66
                        yield "          ";
                    }
                    // line 67
                    yield "        ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['key'], $context['item'], $context['_parent']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 68
                yield "
        </tbody>
      ";
            }
            // line 71
            yield "    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['group_key'], $context['group'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 72
        yield "
  </table>

</div>

";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["ajaxid", "top_label", "element"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "modules/contrib/tmgmt/templates/tmgmt-data-items-form.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  170 => 72,  164 => 71,  159 => 68,  153 => 67,  150 => 66,  144 => 63,  140 => 62,  136 => 60,  134 => 59,  127 => 55,  121 => 52,  115 => 49,  108 => 45,  102 => 42,  97 => 39,  94 => 38,  90 => 37,  87 => 36,  81 => 33,  78 => 32,  76 => 31,  73 => 30,  70 => 29,  66 => 28,  59 => 24,  47 => 15,  44 => 14,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "modules/contrib/tmgmt/templates/tmgmt-data-items-form.html.twig", "C:\\xampp\\htdocs\\drupal-ai\\modules\\contrib\\tmgmt\\templates\\tmgmt-data-items-form.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["for" => 28, "if" => 29];
        static $filters = ["escape" => 15, "first" => 29, "join" => 33];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['for', 'if'],
                ['escape', 'first', 'join'],
                [],
                $this->source
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
