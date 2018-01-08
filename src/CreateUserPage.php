<?php
/*
 * Copyright Sean Proctor
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

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CreateUserPage extends Page
{
    /**
     * Display user form or submit user
     *
     * @param  Context $context
     * @return Response
     */
    public function action(Context $context)
    {
        if (!$context->user->isAdmin()) {
                throw new PermissionException();
        }

        $form = $this->createUserForm($context);

        $form->handleRequest($context->request);
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processForm($context, $form->getData());
        }
        
        // else
        return new Response($context->twig->render("user_create.html.twig", array('form' => $form->createView())));
    }

    /**
     * @param Context $context
     * @param array $data
     * @return Response
     */
    private function processForm(Context $context, $data)
    {
        $uid = $context->db->createUser($data['username'], $data['password'], $data['make_admin']);
    
        if (!empty($data['groups'])) {
            foreach ($data['groups'] as $gid) {
                $context->db->userAddGroup($uid, $gid);
            }
        }

        $context->addMessage(__('user-created-notification'));

        return new RedirectResponse($context->createUrl('admin'));
    }

    private function createUserForm(Context $context)
    {
        $groups = array();
        foreach ($context->db->getGroups() as $group) {
            $groups[$group['name']] = $group['gid'];
        }

        $builder = $context->getFormFactory()->createBuilder();
        $builder->add('username', TextType::class, array('label' => __('username-label')))
        ->add(
            'password',
            RepeatedType::class,
            array(
                'type' => PasswordType::class,
                'invalid_message' => __('password-mismatch-error'),
                'first_options' => ['label' => __('password-label'), 'attr' => ['placeholder' => __('password-label')]],
                'second_options' =>
                    ['label' => __('repeat-password-label'), 'attr' => ['placeholder' => __('password-label')]]
            )
        )
        ->add('make_admin', CheckboxType::class, array('label' => __('make-admin-label'), 'required' => false))
        ->add(
            'groups',
            ChoiceType::class,
            array('label' => __('groups-label'), 'choices' => $groups, 'multiple' => true, 'required' => false)
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($context) {
                $form = $event->getForm();
                $data = $form->getData();
                if ($context->db->getUserByName($data['username']) != null) {
                    $form->get('username')->addError(new FormError(__('user-exists-error')));
                }
            }
        );

        return $builder->getForm();
    }
}
