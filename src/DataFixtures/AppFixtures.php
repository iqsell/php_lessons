<?php
namespace App\DataFixtures;

use App\Entity\Department;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create Departments

        $departmentData = [
            'Главный',
            'НеГлавный',
            'Бесполезный',
        ];

        foreach ($departmentData as $i => $name) {
            $department = new Department();
            $department->setName($name);
            $manager->persist($department);
            $this->addReference('department_' . $i, $department);
            $departments[] = $department;
        }


        // Create Users
        for ($i = 0; $i < 20; $i++) {
            $user = new User();
            $user->setFirstName('user ' . $i);
            $user->setLastName('product ' . $i);
            $user->setAge(mt_rand(10, 100));
            $user->setStatus(mt_rand(0, 1));
            $user->setEmail('user ' . $i . '@gmail.com');
            $user->setTelegram('user ' . $i);
            $user->setAddress('address ' . $i);

            $randomDepartmentIndex = array_rand($departments);
            $user->setDepartment($departments[$randomDepartmentIndex]);

            $manager->persist($user);
        }

        $manager->flush();
    }
}
