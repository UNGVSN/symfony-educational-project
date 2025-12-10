<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Form\FormFactory;
use App\Form\ContactType;
use App\Form\FormView;
use App\Http\Request;

/**
 * This example demonstrates how to render a form as HTML.
 *
 * In a real application, you would use a template engine (Twig, Blade, etc.)
 * but this shows the basic concept of converting FormView to HTML.
 */

// Helper function to render a form field
function renderField(FormView $fieldView): string
{
    $html = '<div class="form-group">' . "\n";

    // Label
    if ($fieldView->vars['label']) {
        $required = $fieldView->vars['required'] ? ' <span class="required">*</span>' : '';
        $html .= sprintf(
            '  <label for="%s">%s%s</label>' . "\n",
            $fieldView->vars['id'],
            htmlspecialchars($fieldView->vars['label']),
            $required
        );
    }

    // Determine input type
    $type = $fieldView->vars['attr']['type'] ?? 'text';

    if ($type === 'submit') {
        // Render as button
        $html .= sprintf(
            '  <button type="submit" id="%s" name="%s" class="%s">%s</button>' . "\n",
            $fieldView->vars['id'],
            $fieldView->vars['name'],
            $fieldView->vars['attr']['class'] ?? 'btn',
            htmlspecialchars($fieldView->vars['label'] ?? 'Submit')
        );
    } else {
        // Render as input
        $attributes = [];
        $attributes[] = sprintf('type="%s"', $type);
        $attributes[] = sprintf('id="%s"', $fieldView->vars['id']);
        $attributes[] = sprintf('name="%s"', $fieldView->vars['name']);
        $attributes[] = sprintf('value="%s"', htmlspecialchars($fieldView->vars['value'] ?? ''));

        if ($fieldView->vars['required']) {
            $attributes[] = 'required';
        }

        if ($fieldView->vars['disabled']) {
            $attributes[] = 'disabled';
        }

        // Additional attributes
        foreach ($fieldView->vars['attr'] as $key => $value) {
            if ($key !== 'type') {
                if (is_bool($value)) {
                    $attributes[] = $value ? $key : '';
                } else {
                    $attributes[] = sprintf('%s="%s"', $key, htmlspecialchars($value));
                }
            }
        }

        $html .= '  <input ' . implode(' ', array_filter($attributes)) . '>' . "\n";
    }

    // Errors
    if (!empty($fieldView->vars['errors'])) {
        foreach ($fieldView->vars['errors'] as $error) {
            $html .= sprintf(
                '  <div class="error">%s</div>' . "\n",
                htmlspecialchars($error->getMessage())
            );
        }
    }

    $html .= '</div>' . "\n";

    return $html;
}

// Helper function to render entire form
function renderForm(FormView $formView, string $action = '', string $method = 'POST'): string
{
    $html = sprintf('<form action="%s" method="%s">' . "\n", $action, $method);

    foreach ($formView->children as $name => $childView) {
        $html .= renderField($childView);
    }

    $html .= '</form>' . "\n";

    return $html;
}

// ============================================================================
// Example: Render Contact Form
// ============================================================================

echo "<!DOCTYPE html>\n";
echo "<html>\n";
echo "<head>\n";
echo "  <meta charset=\"UTF-8\">\n";
echo "  <title>Contact Form Example</title>\n";
echo "  <style>\n";
echo "    body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }\n";
echo "    .form-group { margin-bottom: 15px; }\n";
echo "    label { display: block; margin-bottom: 5px; font-weight: bold; }\n";
echo "    .required { color: red; }\n";
echo "    input[type=\"text\"], input[type=\"email\"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }\n";
echo "    .btn { padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }\n";
echo "    .btn:hover { background-color: #0056b3; }\n";
echo "    .error { color: red; margin-top: 5px; font-size: 14px; }\n";
echo "    .success { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; }\n";
echo "  </style>\n";
echo "</head>\n";
echo "<body>\n";

echo "  <h1>Contact Form</h1>\n";

// Create form
$formFactory = new FormFactory();
$form = $formFactory->create(ContactType::class);

// Handle submission
$request = Request::createFromGlobals();

if ($request->getMethod() === 'POST') {
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();

        echo "  <div class=\"success\">\n";
        echo "    <strong>Success!</strong> Your message has been sent.\n";
        echo "    <ul>\n";
        foreach ($data as $key => $value) {
            if ($key !== 'submit') {
                echo "      <li><strong>" . htmlspecialchars(ucfirst($key)) . ":</strong> " . htmlspecialchars($value) . "</li>\n";
            }
        }
        echo "    </ul>\n";
        echo "  </div>\n";
    }
}

// Render form
$formView = $form->createView();
echo renderForm($formView, $_SERVER['PHP_SELF'] ?? '', 'POST');

echo "</body>\n";
echo "</html>\n";
