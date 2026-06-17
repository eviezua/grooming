<?php

namespace App\Controller\Admin;

use App\Entity\Districts;
use App\Enum\Status;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DistrictsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Districts::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('name', 'District'),
            AssociationField::new('city', 'City'),
            ChoiceField::new('status')
                ->setChoices([
                    'Awaiting' => Status::Awaiting,
                    'Approved' => Status::Approved,
                    'Rejected' => Status::Rejected,
                    'Inactive' => Status::Inactive,
                ])
                ->renderAsBadges([
                    Status::Awaiting->value => 'warning',
                    Status::Approved->value => 'success',
                    Status::Rejected->value => 'danger',
                    Status::Inactive->value => 'secondary',
                ]),
        ];
    }
}
