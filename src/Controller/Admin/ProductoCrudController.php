<?php

namespace App\Controller\Admin;

use App\Entity\Producto;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;

class ProductoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Producto::class;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
    
        public function configureFilters(Filters $filters): Filters
    {
            return $filters
                    ->add('nombre')
                    ->add('cod');

    }
}
