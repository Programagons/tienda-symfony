<?php

namespace App\Controller\Admin;

use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsuarioCrudController extends AbstractCrudController
{
    # Servicio encargado de hashear contraseñas
    private UserPasswordHasherInterface $passwordHasher;

    # Dependencias del hasher 
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    # Qué entidad maneja este crud
    public static function getEntityFqcn(): string
    {
        return Usuario::class;
    }

    # Cuando se crea un nuevo usuario se verifica su identidad y se hashea la contraseña 
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Usuario) {
            $this->hashPassword($entityInstance);
        }

        # Guardamos la entidad
        parent::persistEntity($entityManager, $entityInstance);
    }

    # Igual que el anterior pero al actualizar
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Usuario) {
            $this->hashPassword($entityInstance);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    # Método para hashear la contraseña
    private function hashPassword(Usuario $usuario): void
    {
        if (!$usuario->getPlainPassword()) {
            return;
        }

        # Generamos una contraseña hasheada 
        $hashedPassword = $this->passwordHasher->hashPassword(
            $usuario,
            $usuario->getPlainPassword()
        );

        $usuario->setPassword($hashedPassword);
        $usuario->eraseCredentials();
    }
}
