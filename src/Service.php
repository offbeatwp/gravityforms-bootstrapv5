<?php
namespace OffbeatWP\GravityFormsBootstrapV5;

use OffbeatWP\Services\AbstractService;
use OffbeatWP\Contracts\View;

final class Service extends AbstractService
{
    public function register(View $view)
    {
        add_filter('gform_get_form_filter', [$this, 'bootstrapClasses'], 10, 2);
        add_filter('gform_field_container', [$this, 'bootstrapContainer'], 10, 2);
        add_filter('gform_field_content', [$this, 'fieldBootstrapClasses']);

        add_filter('gform_submit_button', [$this, 'buttonClassFrontend'], 10, 2);
        add_filter('gform_submit_button', [$this, 'inputToButton'], 10, 2);

        if (is_admin()) {
            add_action('gform_field_appearance_settings', [$this, 'customAdminFields']);
            add_action('gform_editor_js', [$this, 'customAdminFieldSizes']);
            add_filter('gform_tooltips', [$this, 'customAdminFieldTooltips']);
        }
    }

    /**
     * @param string $fieldContainer
     * @param mixed[] $field
     * @return mixed[]|string|null
     */
    public static function bootstrapContainer($fieldContainer, $field)
    {
        $replacement = 'class=$1$2 form-group';

        if (strpos($field['cssClass'], 'col-') === false) {
            if (!$field['colXs']) {
                $field['colXs'] = '12';
            }

            if ($field['colXs']) {
                $replacement .= ' col-' . $field['colXs'];
            }

            if ($field['colMd']) {
                $replacement .= ' col-md-' . $field['colMd'];
            }

            if ($field['colLg']) {
                $replacement .= ' col-lg-' . $field['colLg'];
            }
        }

        $replacement .= '$3';

        $fieldContainer = preg_replace('/class=(\'|")([^\'"]+)(\'|")/', $replacement, $fieldContainer);

        return $fieldContainer;
    }

    /**
     * @param string $formHtml
     * @return string
     */
    public function bootstrapClasses($formHtml)
    {
        if (preg_match("/class='[^']*gform_validation_error[^']*'/", $formHtml)) {
            preg_match_all("/class='(gfield [^']+)'/", $formHtml, $gFields);

            if (!empty($gFields[0])) {
                foreach ($gFields[0] as $gFieldIndex => $gField) {
                    $class = ' is-valid';

                    if (strpos($gFields[1][$gFieldIndex], 'gfield_error') !== false) {
                        $class = ' is-invalid';
                    }

                    $formHtml = str_replace($gField, "class='" . $gFields[1][$gFieldIndex] . $class . "'", $formHtml);
                }
            }
        }

        return $formHtml;
    }

    /**
     * @param string $fieldContent
     * @return string
     */
    public function fieldBootstrapClasses($fieldContent)
    {
        if (strpos($fieldContent, '<select') !== false) {
            preg_match_all('/<select[^>]+>/', $fieldContent, $selectTags);

            if (!empty($selectTags[0])) {
                foreach ($selectTags[0] as $selectTag) {
                    if (strpos($selectTag, 'class=') !== false) {
                        $fieldContent = str_replace($selectTag, preg_replace("/class='([^']+)'/", "class='$1 form-select'", $selectTag), $fieldContent);
                    } else {
                        $fieldContent = str_replace($selectTag, str_replace('<select', '<select class="form-select"', $selectTag), $fieldContent);
                    }
                }
            }
        }

        if (preg_match("/type='(radio|checkbox)'/", $fieldContent)) {
            preg_match_all("/(<input[^>]*type='(radio|checkbox)'[^>]+>)\s*<label[^>]+>(.*)<\/label>/misU", $fieldContent, $radioTags);

            if (!empty($radioTags[0])) {
                foreach ($radioTags[0] as $radioIndex => $radioTag) {
                    $inputField = $radioTag;
                    $inputField = str_replace('<input', "<input class='form-check-input'", $inputField);
                    $inputField = str_replace('<label', "<label class='form-check-label'", $inputField);

                    $fieldContent = str_replace($radioTag, '<div class="form-check custom-' . $radioTags[2][$radioIndex] . '">' . $inputField . '</div>', $fieldContent);
                }
            }
        }

        if (preg_match("/type='file'/", $fieldContent)) {
            preg_match_all("/<input[^>]*type='file'[^>]+>/", $fieldContent, $inputFileTags);

            if (!empty($inputFileTags[0])) {
                foreach ($inputFileTags[0] as $inputFileTag) {
                    $inputFileTagBs = preg_replace("/class='([^']+)'/", "class='$1 form-control'", $inputFileTag);
                    $fieldContent = str_replace($inputFileTag, '<label class="form-label">' . __('Choose file', 'offbeatwp') . '</label>' . $inputFileTagBs, $fieldContent);
                }
            }

        }

        return $fieldContent;
    }

    /**
     * @param string $button
     * @param mixed[] $form
     * @return string
     */
    public static function buttonClassFrontend($button, $form): string
    {
        if (empty($form['button']['class'])) {
            return $button;
        }

        return preg_replace("/class='([\.a-zA-Z_ -]+)'/", "class='$1 btn " . $form['button']['class']. "'", $button);
    }

    /**
     * @param string $buttonInput
     * @param mixed[] $form
     * @return string
     */
    public static function inputToButton($buttonInput, $form)
    {
        preg_match('/<input([^\/>]*)(\s\/)*>/', $buttonInput, $buttonMatch);

        $buttonAtts = str_replace("value='" . $form['button']['text'] . "' ", '', $buttonMatch[1]);

        return '<button ' . $buttonAtts . '>' . $form['button']['text'] . '</button>';
    }

    /**
     * @param int $position
     * @return void
     */
    public function customAdminFields($position)
    {
        if ($position !== 400 || !function_exists('gform_tooltip')) {
            return;
        }
        ?>

        <li class="col_xs_setting field_setting">
            <ul>
                <li>
                    <label for="field_col_xs" class="section_label">
                        <?php esc_html_e('Field Size (mobile)', 'offbeatwp');?>
                        <?php gform_tooltip('form_field_col_xs');?>
                    </label>

                    <select id="field_col_xs" onchange="SetFieldProperty('colXs', this.value)">
                        <option value="12">12</option>
                        <option value="11">11</option>
                        <option value="10">10</option>
                        <option value="9">9</option>
                        <option value="8">8</option>
                        <option value="7">7</option>
                        <option value="6">6</option>
                        <option value="5">5</option>
                        <option value="4">4</option>
                        <option value="3">3</option>
                        <option value="2">2</option>
                        <option value="1">1</option>
                    </select>
                </li>
            </ul>
        </li>

        <li class="col_md_setting field_setting">
            <ul>
                <li>
                    <label for="field_col_md" class="section_label">
                        <?php esc_html_e('Field Size (tablet)', 'offbeatwp');?>
                        <?php gform_tooltip('form_field_col_md');?>
                    </label>

                    <select id="field_col_md" onchange="SetFieldProperty('colMd', this.value)">
                        <option value="">Inherit</option>
                        <option value="12">12</option>
                        <option value="11">11</option>
                        <option value="10">10</option>
                        <option value="9">9</option>
                        <option value="8">8</option>
                        <option value="7">7</option>
                        <option value="6">6</option>
                        <option value="5">5</option>
                        <option value="4">4</option>
                        <option value="3">3</option>
                        <option value="2">2</option>
                        <option value="1">1</option>
                    </select>
                </li>
            </ul>
        </li>

        <li class="col_lg_setting field_setting">
            <ul>
                <li>
                    <label for="field_col_lg" class="section_label">
                        <?php esc_html_e('Field Size (desktop)', 'offbeatwp');?>
                        <?php gform_tooltip('form_field_col_lg');?>
                    </label>

                    <select id="field_col_lg" onchange="SetFieldProperty('colLg', this.value)">
                        <option value="">Inherit</option>
                        <option value="12">12</option>
                        <option value="11">11</option>
                        <option value="10">10</option>
                        <option value="9">9</option>
                        <option value="8">8</option>
                        <option value="7">7</option>
                        <option value="6">6</option>
                        <option value="5">5</option>
                        <option value="4">4</option>
                        <option value="3">3</option>
                        <option value="2">2</option>
                        <option value="1">1</option>
                    </select>
                </li>
            </ul>
        </li>
        <?php
    }

    public function customAdminFieldSizes(): void
    {
        ?>
        <script type="text/javascript">
            jQuery.map(fieldSettings, (el, i) => {
                fieldSettings[i] += ', .col_xs_setting';
                fieldSettings[i] += ', .col_md_setting';
                fieldSettings[i] += ', .col_lg_setting';
            });

            jQuery(document).on('gform_load_field_settings', (ev, field) => {
                jQuery('#field_col_xs').val(field.colXs || '12');
                jQuery('#field_col_md').val(field.colMd || '');
                jQuery('#field_col_lg').val(field.colLg || '');
            });

            // Disable original field size setting
            jQuery(document).ready(() => {
                jQuery('.field_setting.size_setting').remove();
            });
        </script>
        <?php

    }

    /**
     * @param mixed[] $tooltips
     * @return mixed[]
     */
    public function customAdminFieldTooltips($tooltips)
    {
        $tooltips['form_field_col_xs'] = sprintf(
            '<h6>%s</h6>%s',
            __('Field Size (mobile)', 'offbeatwp'),
            __('Select a form field size from the available options. This will set the width of the field on (most) mobile devices and up. If no field sizes are set for larger devices this setting will be inherited.', 'offbeatwp')
        );

        $tooltips['form_field_col_md'] = sprintf(
            '<h6>%s</h6>%s',
            __('Field Size (tablet)', 'offbeatwp'),
            __('Select a form field size from the available options. This will set the width of the field on (most) tablet devices and up. If no field sizes are set for larger devices this setting will be inherited.', 'offbeatwp')
        );

        $tooltips['form_field_col_lg'] = sprintf(
            '<h6>%s</h6>%s',
            __('Field Size (desktop)', 'offbeatwp'),
            __('Select a form field size from the available options. This will set the width of the field on (most) desktop devices and up.', 'offbeatwp')
        );

        return $tooltips;
    }
}