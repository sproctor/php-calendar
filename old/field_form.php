<?php
/*
 * Copyright 2010 Sean Proctor
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// require_once "$phpc_includes_path/form.php";

function field_form()
{
    global $phpc_script, $vars, $phpcdb, $phpcid;

        $form = new Form($phpc_script, __('Field Form'));
        $form->add_part(new FormFreeQuestion('name', __('Name'), false, 32, true));

    if (isset($vars['cid'])) {
        $form->add_hidden('cid', $vars['cid']);
        $cid = $vars['cid'];
    } else {
        $cid = $phpcid;
    }

    $form->add_hidden('action', 'field_submit');
    $form->add_hidden('phpcid', $phpcid);
    $form->add_part(new FormCheckBoxQuestion('required', __('Required?')));
        $form->add_part(new FormFreeQuestion('format', __('Format')));
    $form->add_part(new FormSubmitButton(__("Submit Field")));

    if (isset($vars['fid'])) {
        $form->add_hidden('fid', $vars['fid']);
        $field = $phpcdb->get_field($vars['fid']);
        $defaults = array(
        'name' => htmlspecialchars($field['name']),
        'required' => htmlspecialchars($field['required']),
        'format' => htmlspecialchars($field['format']),
        );
    } else {
        $defaults = array();
    }
        return $form->get_form($defaults);
}
