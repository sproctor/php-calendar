<?php
/*
 * Copyright 2017 Sean Proctor
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

namespace PhpCalendar;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AdminPage extends Page
{
    /**
     * Display admin page and process forms
     *
     * @param  Context $context
     * @return Response
     */
    public function action(Context $context)
    {
        $import_form = $this->getImportForm($context);
        $import_form->handleRequest($context->request);
        if ($import_form->isSubmitted() && $import_form->isValid()) {
            // $this->processImportForm($context, $import_form->getData());
        }

        return new Response(
            $context->twig->render(
                "admin.html.twig",
                array('import_form' => $import_form->createView())
            )
        );
    }

    /**
     * @param Context $context
     * @return Form
     */
    private function getImportForm(Context $context)
    {
        $builder = $context->getFormFactory()->createBuilder();

        $builder->add('host', TextType::class, array('label' => __('MySQL Host Name'), 'data' => 'localhost'))
        ->add('dbname', TextType::class, array('label' => __('MySQL Database Name'), 'data' => 'calendar'))
        ->add('port', IntegerType::class, array('label' => __('MySQL Port Number'), 'required' => false)) // TODO add message: __('Leave blank for default')
        ->add('username', TextType::class, array('label' => __('MySQL User Name')))
        ->add('passwd', PasswordType::class, array('label' => __('MySQL User Password')))
        ->add('prefix', TextType::class, array('label' => __('PHP-Calendar Table Prefix'), 'data' => 'phpc_'));

        return $builder->getForm();
    }
}
