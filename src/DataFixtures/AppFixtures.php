<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Doctor;
use App\Entity\Establishment;
use App\Entity\Pharmacy;
use App\Entity\Appointment;
use App\Entity\Medication;
use App\Entity\Commune;
use App\Entity\PharmacyDutySchedule;
use App\Entity\Speciality;
use App\Entity\City;
use App\Entity\AppSetting;
use App\Entity\Review;
use App\Entity\EmergencyContact;
use App\Entity\HealthTip;
use App\Entity\MedicalHistory;
use App\Entity\Notification;
use App\Utils\DataType;
use App\Utils\RevieweeType;
use App\Utils\HealthTipCategory;
use App\Utils\EstablishmentType;
use App\Entity\DoctorEstablishment;
use App\Utils\DoctorEstablishmentStatus;
use App\Utils\MedicalHistoryCategory;
use App\Utils\NotificationPriority;
use App\Utils\NotificationType;
use App\Utils\Roles;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $specialities = $this->createSpecialities($manager);
        $cities = $this->createCities($manager);
        $this->createAppSettings($manager);
        $manager->flush(); // Flush reference data first
        
        $users = $this->createUsers($manager);
        $manager->flush(); // Flush users first
        
        $doctors = $this->createDoctors($manager, $users, $specialities);
        $establishments = $this->createEstablishments($manager);
        $manager->flush(); // Flush doctors and establishments
        
        $this->createDoctorEstablishments($manager, $doctors, $establishments);
        $communes = $this->createCommunes($manager);
        $pharmacies = $this->createPharmacies($manager, $communes);
        $this->createDutySchedules($manager, $pharmacies);
        $appointments = $this->createAppointments($manager, $users, $doctors, $establishments);
        $this->createMedications($manager);
        $this->createReviews($manager, $users, $doctors, $establishments);
        $this->createEmergencyContacts($manager, $users);
        $this->createHealthTips($manager, $users);
        $this->createMedicalHistories($manager, $users, $doctors, $appointments);
        $this->createNotifications($manager, $users);
        $manager->flush();
    }

    private function createUsers(ObjectManager $manager): array
    {
        $users = [];
        
        // Admin
        $admin = new User();
        $admin->setEmail('admin@healthapp.com')
            ->setFullName('Administrateur Principal')
            ->setPhone('+33123456789')
            ->setAddress('123 Rue de la Santé, Paris')
            ->setUserJob('ADMIN')
            ->setRoles([Roles::ROLE_ADMIN->value])
            ->setIsActivated(true)
            ->setProfileImage('users/admin.jpg')
            ->setLocale('fr');
        
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);
        $users[] = $admin;

        // Médecins
        $doctorUsers = [
            ['email' => 'dr.martin@healthapp.com', 'fullName' => 'Dr. Pierre Martin', 'phone' => '+33123456790', 'address' => '45 Avenue des Médecins, Lyon', 'image' => 'users/dr-martin.jpg'],
            ['email' => 'dr.dubois@healthapp.com', 'fullName' => 'Dr. Marie Dubois', 'phone' => '+33123456791', 'address' => '78 Boulevard de la Santé, Marseille', 'image' => 'users/dr-dubois.jpg'],
            ['email' => 'dr.bernard@healthapp.com', 'fullName' => 'Dr. Jean Bernard', 'phone' => '+33123456792', 'address' => '12 Rue du Cœur, Toulouse', 'image' => 'users/dr-bernard.jpg']
        ];

        foreach ($doctorUsers as $userData) {
            $user = new User();
            $user->setEmail($userData['email'])
                ->setFullName($userData['fullName'])
                ->setPhone($userData['phone'])
                ->setAddress($userData['address'])
                ->setUserJob('MEDECIN')
                ->setRoles([Roles::ROLE_MEDECIN->value])
                ->setIsActivated(true)
                ->setProfileImage($userData['image'])
                ->setLocale('fr');
            
            $user->setPassword($this->passwordHasher->hashPassword($user, 'doctor123'));
            $manager->persist($user);
            $users[] = $user;
        }

        // Patients
        $patientUsers = [
            ['email' => 'patient1@healthapp.com', 'fullName' => 'Sophie Leroy', 'phone' => '+33123456793', 'address' => '34 Rue des Patients, Nice', 'image' => 'users/patient1.jpg'],
            ['email' => 'patient2@healthapp.com', 'fullName' => 'Michel Moreau', 'phone' => '+33123456794', 'address' => '56 Avenue de la Guérison, Bordeaux', 'image' => 'users/patient2.jpg']
        ];

        foreach ($patientUsers as $userData) {
            $user = new User();
            $user->setEmail($userData['email'])
                ->setFullName($userData['fullName'])
                ->setPhone($userData['phone'])
                ->setAddress($userData['address'])
                ->setUserJob('AUTRE')
                ->setRoles([Roles::ROLE_USER->value])
                ->setIsActivated(true)
                ->setProfileImage($userData['image'])
                ->setLocale('fr');
            
            $user->setPassword($this->passwordHasher->hashPassword($user, 'patient123'));
            $manager->persist($user);
            $users[] = $user;
        }

        return $users;
    }

    private function createDoctors(ObjectManager $manager, array $users, array $specialities): array
    {
        $doctors = [];
        $doctorData = [
            [
                'userIndex' => 1,
                'licenseNumber' => 'FR123456789',
                'specialityIndex' => 0,
                'yearsOfExperience' => 15,
                'consultationFee' => '80.00',
                'isEmergencyAvailable' => true,
                'rating' => '4.8',
                'totalReviews' => 127,
                'bio' => 'Cardiologue expérimenté spécialisé dans les maladies cardiovasculaires.',
                'languages' => ['Français', 'Anglais', 'Espagnol'],
                'availabilitySchedule' => [
                    'lundi' => ['09:00-12:00', '14:00-18:00'],
                    'mardi' => ['09:00-12:00', '14:00-18:00'],
                    'mercredi' => ['09:00-12:00'],
                    'jeudi' => ['09:00-12:00', '14:00-18:00'],
                    'vendredi' => ['09:00-12:00', '14:00-17:00']
                ]
            ],
            [
                'userIndex' => 2,
                'licenseNumber' => 'FR987654321',
                'specialityIndex' => 1,
                'yearsOfExperience' => 12,
                'consultationFee' => '70.00',
                'isEmergencyAvailable' => false,
                'rating' => '4.6',
                'totalReviews' => 89,
                'bio' => 'Dermatologue spécialisée dans les affections cutanées.',
                'languages' => ['Français', 'Anglais'],
                'availabilitySchedule' => [
                    'lundi' => ['08:30-12:30', '13:30-17:30'],
                    'mardi' => ['08:30-12:30', '13:30-17:30'],
                    'jeudi' => ['08:30-12:30', '13:30-17:30'],
                    'vendredi' => ['08:30-12:30']
                ]
            ],
            [
                'userIndex' => 3,
                'licenseNumber' => 'FR456789123',
                'specialityIndex' => 2,
                'yearsOfExperience' => 20,
                'consultationFee' => '60.00',
                'isEmergencyAvailable' => true,
                'rating' => '4.9',
                'totalReviews' => 203,
                'bio' => 'Médecin généraliste avec approche holistique.',
                'languages' => ['Français', 'Anglais', 'Italien'],
                'availabilitySchedule' => [
                    'lundi' => ['08:00-12:00', '14:00-19:00'],
                    'mardi' => ['08:00-12:00', '14:00-19:00'],
                    'mercredi' => ['08:00-12:00', '14:00-19:00'],
                    'jeudi' => ['08:00-12:00', '14:00-19:00'],
                    'vendredi' => ['08:00-12:00', '14:00-18:00'],
                    'samedi' => ['09:00-13:00']
                ]
            ]
        ];

        foreach ($doctorData as $data) {
            $doctor = new Doctor();
            $doctor->setUserId($users[$data['userIndex']]->getId())
                ->setUser($users[$data['userIndex']])
                ->setLicenseNumber($data['licenseNumber'])
                ->setSpeciality($specialities[$data['specialityIndex']]->getName())
                ->setYearsOfExperience($data['yearsOfExperience'])
                ->setConsultationFee($data['consultationFee'])
                ->setIsEmergencyAvailable($data['isEmergencyAvailable'])
                ->setRating($data['rating'])
                ->setTotalReviews($data['totalReviews'])
                ->setBio($data['bio'])
                ->setLanguages($data['languages'])
                ->setAvailabilitySchedule($data['availabilitySchedule'])
                ->setIsVerified(true);

            $manager->persist($doctor);
            $doctors[] = $doctor;
        }
        
        return $doctors;
    }

    private function createEstablishments(ObjectManager $manager): array
    {
        $establishments = [];
        $establishmentData = [
            [
                'name' => 'Hôpital Saint-Louis',
                'type' => EstablishmentType::HOSPITAL,
                'address' => '1 Avenue Claude Vellefaux, 75010 Paris',
                'city' => 'Paris',
                'phone' => '+33142499000',
                'email' => 'contact@hopital-saint-louis.fr',
                'latitude' => '48.8719',
                'longitude' => '2.3698',
                'isPublic' => true,
                'isEmergency' => true,
                'services' => ['Cardiologie', 'Urgences', 'Chirurgie'],
                'openingHours' => ['24h/24', '7j/7'],
                'rating' => '4.2',
                'totalReviews' => 156,
                'image' => 'establishments/hopital-saint-louis.jpg'
            ],
            [
                'name' => 'Clinique du Parc',
                'type' => EstablishmentType::CLINIC,
                'address' => '155 Ter Boulevard Stalingrad, 69006 Lyon',
                'city' => 'Lyon',
                'phone' => '+33478247000',
                'email' => 'accueil@clinique-parc.fr',
                'latitude' => '45.7640',
                'longitude' => '4.8357',
                'isPublic' => false,
                'isEmergency' => false,
                'services' => ['Dermatologie', 'Médecine générale'],
                'openingHours' => [
                    'lundi' => '08:00-18:00',
                    'mardi' => '08:00-18:00',
                    'mercredi' => '08:00-18:00',
                    'jeudi' => '08:00-18:00',
                    'vendredi' => '08:00-17:00'
                ],
                'rating' => '4.5',
                'totalReviews' => 89,
                'image' => 'establishments/clinique-du-parc.jpg'
            ],
            [
                'name' => 'Cabinet Médical Toulouse Centre',
                'type' => EstablishmentType::PRIVATE_PRACTICE,
                'address' => '12 Place du Capitole, 31000 Toulouse',
                'city' => 'Toulouse',
                'phone' => '+33561230000',
                'email' => 'contact@cabinet-toulouse.fr',
                'latitude' => '43.6047',
                'longitude' => '1.4442',
                'isPublic' => false,
                'isEmergency' => false,
                'services' => ['Médecine générale', 'Consultations'],
                'openingHours' => [
                    'lundi' => '08:00-19:00',
                    'mardi' => '08:00-19:00',
                    'mercredi' => '08:00-19:00',
                    'jeudi' => '08:00-19:00',
                    'vendredi' => '08:00-18:00',
                    'samedi' => '09:00-13:00'
                ],
                'rating' => '4.8',
                'totalReviews' => 203,
                'image' => 'establishments/cabinet-toulouse.jpg'
            ]
        ];

        foreach ($establishmentData as $data) {
            $establishment = new Establishment();
            $establishment->setName($data['name'])
                ->setType($data['type'])
                ->setAddress($data['address'])
                ->setCity($data['city'])
                ->setPhone($data['phone'])
                ->setEmail($data['email'])
                ->setLatitude($data['latitude'])
                ->setLongitude($data['longitude'])
                ->setIsPublic($data['isPublic'])
                ->setIsEmergency($data['isEmergency'])
                ->setServices($data['services'])
                ->setOpeningHours($data['openingHours'])
                ->setRating($data['rating'])
                ->setTotalReviews($data['totalReviews'])
                ->setImage($data['image'])
                ->setIsActive(true);

            $manager->persist($establishment);
            $establishments[] = $establishment;
        }

        return $establishments;
    }

    private function createDoctorEstablishments(ObjectManager $manager, array $doctors, array $establishments): void
    {
        // Dr. Martin -> Hôpital Saint-Louis
        $doctorEst1 = new DoctorEstablishment();
        $doctorEst1->setDoctorId($doctors[0]->getId())
            ->setEstablishmentId($establishments[0]->getId())
            ->setStatus(DoctorEstablishmentStatus::ACTIVE)
            ->setIsPrimary(true)
            ->setWorkingHours([
                'lundi' => ['09:00-12:00', '14:00-18:00'],
                'mardi' => ['09:00-12:00', '14:00-18:00'],
                'jeudi' => ['09:00-12:00', '14:00-18:00']
            ])
            ->setStartDate(new \DateTime('2020-01-01'));

        // Dr. Dubois -> Clinique du Parc
        $doctorEst2 = new DoctorEstablishment();
        $doctorEst2->setDoctorId($doctors[1]->getId())
            ->setEstablishmentId($establishments[1]->getId())
            ->setStatus(DoctorEstablishmentStatus::ACTIVE)
            ->setIsPrimary(true)
            ->setWorkingHours([
                'lundi' => ['08:30-12:30', '13:30-17:30'],
                'mardi' => ['08:30-12:30', '13:30-17:30'],
                'jeudi' => ['08:30-12:30', '13:30-17:30']
            ])
            ->setStartDate(new \DateTime('2019-06-01'));

        // Dr. Bernard -> Cabinet Médical Toulouse Centre
        $doctorEst3 = new DoctorEstablishment();
        $doctorEst3->setDoctorId($doctors[2]->getId())
            ->setEstablishmentId($establishments[2]->getId())
            ->setStatus(DoctorEstablishmentStatus::ACTIVE)
            ->setIsPrimary(true)
            ->setWorkingHours([
                'lundi' => ['08:00-12:00', '14:00-19:00'],
                'mardi' => ['08:00-12:00', '14:00-19:00'],
                'mercredi' => ['08:00-12:00', '14:00-19:00'],
                'jeudi' => ['08:00-12:00', '14:00-19:00'],
                'vendredi' => ['08:00-12:00', '14:00-18:00'],
                'samedi' => ['09:00-13:00']
            ])
            ->setStartDate(new \DateTime('2018-03-01'));

        $manager->persist($doctorEst1);
        $manager->persist($doctorEst2);
        $manager->persist($doctorEst3);
    }

    private function createCommunes(ObjectManager $manager): array
    {
        $communes = [];
        $communeData = [
            ['name' => '3ème Arrondissement', 'city' => 'Paris'],
            ['name' => '2ème Arrondissement', 'city' => 'Lyon'],
            ['name' => 'Centre-Ville', 'city' => 'Toulouse']
        ];

        foreach ($communeData as $data) {
            $commune = new Commune();
            $commune->setName($data['name'])
                ->setCity($data['city']);
            $manager->persist($commune);
            $communes[] = $commune;
        }

        return $communes;
    }

    private function createPharmacies(ObjectManager $manager, array $communes): array
    {
        $pharmacies = [];
        $pharmacyData = [
            [
                'name' => 'Pharmacie de la République',
                'address' => '45 Place de la République, 75003 Paris',
                'city' => 'Paris',
                'postalCode' => '75003',
                'phone' => '+33142720000',
                'latitude' => '48.8676',
                'longitude' => '2.3631',
                'isOpen24h' => true,
                'hasDelivery' => true,
                'openOnHolidays' => true,
                'rating' => '4.3',
                'services' => ['Médicaments', 'Parapharmacie', 'Orthopédie', 'Livraison'],
                'openingHours' => [
                    'lundi' => '09:00-19:00',
                    'mardi' => '09:00-19:00',
                    'mercredi' => '09:00-19:00',
                    'jeudi' => '09:00-19:00',
                    'vendredi' => '09:00-19:00',
                    'samedi' => '09:00-18:00',
                    'dimanche' => null
                ],
                'communeIndex' => 0
            ],
            [
                'name' => 'Pharmacie du Centre',
                'address' => '12 Rue de la Paix, 69002 Lyon',
                'city' => 'Lyon',
                'postalCode' => '69002',
                'phone' => '+33478420000',
                'latitude' => '45.7578',
                'longitude' => '4.8320',
                'isOpen24h' => false,
                'hasDelivery' => true,
                'openOnHolidays' => false,
                'rating' => '4.6',
                'services' => ['Médicaments', 'Cosmétiques', 'Conseil pharmaceutique'],
                'openingHours' => [
                    'lundi' => '08:30-19:30',
                    'mardi' => '08:30-19:30',
                    'mercredi' => '08:30-19:30',
                    'jeudi' => '08:30-19:30',
                    'vendredi' => '08:30-19:30',
                    'samedi' => '09:00-19:00',
                    'dimanche' => null
                ],
                'communeIndex' => 1
            ],
            [
                'name' => 'Pharmacie Saint-Pierre',
                'address' => '78 Avenue Jean Jaurès, 31000 Toulouse',
                'city' => 'Toulouse',
                'postalCode' => '31000',
                'phone' => '+33561340000',
                'latitude' => '43.6108',
                'longitude' => '1.4442',
                'isOpen24h' => false,
                'hasDelivery' => false,
                'openOnHolidays' => true,
                'rating' => '4.4',
                'services' => ['Médicaments', 'Homéopathie', 'Matériel médical'],
                'openingHours' => [
                    'lundi' => '09:00-19:00',
                    'mardi' => '09:00-19:00',
                    'mercredi' => '09:00-19:00',
                    'jeudi' => '09:00-19:00',
                    'vendredi' => '09:00-19:00',
                    'samedi' => '09:00-18:00',
                    'dimanche' => null
                ],
                'communeIndex' => 2
            ]
        ];

        foreach ($pharmacyData as $data) {
            $pharmacy = new Pharmacy();
            $pharmacy->setName($data['name'])
                ->setAddress($data['address'])
                ->setCity($data['city'])
                ->setPostalCode($data['postalCode'])
                ->setPhone($data['phone'])
                ->setLatitude($data['latitude'])
                ->setLongitude($data['longitude'])
                ->setIsOpen24h($data['isOpen24h'])
                ->setHasDelivery($data['hasDelivery'])
                ->setOpenOnHolidays($data['openOnHolidays'])
                ->setRating($data['rating'])
                ->setServices($data['services'])
                ->setOpeningHours($data['openingHours'])
                ->setCommuneId($communes[$data['communeIndex']]->getId())
                ->setIsActive(true);

            $manager->persist($pharmacy);
            $pharmacies[] = $pharmacy;
        }

        return $pharmacies;
    }

    private function createDutySchedules(ObjectManager $manager, array $pharmacies): void
    {
        $dutyData = [
            [
                'pharmacyIndex' => 0, // Pharmacie de la République
                'startDate' => 'today',
                'endDate' => '+7 days',
                'scheduleType' => 'weekly'
            ],
            [
                'pharmacyIndex' => 1, // Pharmacie du Centre
                'startDate' => '+8 days',
                'endDate' => '+14 days',
                'scheduleType' => 'weekly'
            ],
            [
                'pharmacyIndex' => 2, // Pharmacie Saint-Pierre
                'startDate' => '+15 days',
                'endDate' => '+21 days',
                'scheduleType' => 'weekly'
            ]
        ];

        foreach ($dutyData as $data) {
            $dutySchedule = new PharmacyDutySchedule();
            $dutySchedule->setPharmacyId($pharmacies[$data['pharmacyIndex']]->getId())
                ->setStartDate(new \DateTimeImmutable($data['startDate']))
                ->setEndDate(new \DateTimeImmutable($data['endDate']))
                ->setScheduleType($data['scheduleType'])
                ->setIsActive(true);

            $manager->persist($dutySchedule);
        }
    }

    private function createAppointments(ObjectManager $manager, array $users, array $doctors, array $establishments): array
    {
        $appointments = [];
        $appointmentData = [
            [
                'patientIndex' => 4, // Sophie Leroy
                'doctorIndex' => 0, // Dr. Martin
                'establishmentIndex' => 0, // Hôpital Saint-Louis
                'date' => '+2 days',
                'time' => '10:00',
                'duration' => 30,
                'status' => 'CONFIRMED',
                'reason' => 'Consultation cardiologique de contrôle',
                'consultationFee' => '80.00',
                'isEmergency' => false,
                'patientSymptoms' => 'Douleurs thoraciques légères',
                'priority' => 'NORMAL'
            ],
            [
                'patientIndex' => 5, // Michel Moreau
                'doctorIndex' => 1, // Dr. Dubois
                'establishmentIndex' => 1, // Clinique du Parc
                'date' => '+1 day',
                'time' => '14:30',
                'duration' => 45,
                'status' => 'PENDING',
                'reason' => 'Consultation dermatologique',
                'consultationFee' => '70.00',
                'isEmergency' => false,
                'patientSymptoms' => 'Éruption cutanée sur le bras',
                'priority' => 'NORMAL'
            ],
            [
                'patientIndex' => 4, // Sophie Leroy
                'doctorIndex' => 2, // Dr. Bernard
                'establishmentIndex' => 2, // Cabinet Médical Toulouse Centre
                'date' => '+5 days',
                'time' => '09:15',
                'duration' => 30,
                'status' => 'PENDING',
                'reason' => 'Visite médicale générale',
                'consultationFee' => '60.00',
                'isEmergency' => false,
                'patientSymptoms' => 'Fatigue générale',
                'priority' => 'LOW'
            ],
            [
                'patientIndex' => 5, // Michel Moreau
                'doctorIndex' => 0, // Dr. Martin
                'establishmentIndex' => 0, // Hôpital Saint-Louis
                'date' => 'today',
                'time' => '16:00',
                'duration' => 60,
                'status' => 'CONFIRMED',
                'reason' => 'Urgence cardiaque',
                'consultationFee' => '120.00',
                'isEmergency' => true,
                'patientSymptoms' => 'Douleurs thoraciques intenses, essoufflement',
                'priority' => 'HIGH'
            ]
        ];

        foreach ($appointmentData as $data) {
            $appointment = new Appointment();
            $appointment->setPatientId($users[$data['patientIndex']]->getId())
                ->setDoctorId($doctors[$data['doctorIndex']]->getId())
                ->setEstablishmentId($establishments[$data['establishmentIndex']]->getId())
                ->setAppointmentDate(new \DateTimeImmutable($data['date']))
                ->setAppointmentTime(new \DateTimeImmutable($data['time']))
                ->setDurationMinutes($data['duration'])
                ->setStatus($data['status'])
                ->setReason($data['reason'])
                ->setConsultationFee($data['consultationFee'])
                ->setIsEmergency($data['isEmergency'])
                ->setPatientSymptoms($data['patientSymptoms'])
                ->setPriority($data['priority']);

            $manager->persist($appointment);
            $appointments[] = $appointment;
        }

        return $appointments;
    }

    private function createMedications(ObjectManager $manager): void
    {
        $jsonPath = __DIR__ . '/../../var/medications_enriched_openfda.json';
        if (!file_exists($jsonPath)) {
            $jsonPath = __DIR__ . '/../../var/medications_enriched.json';
        }
        if (!file_exists($jsonPath)) {
            return;
        }

        $medicationData = json_decode(file_get_contents($jsonPath), true);
        if (!is_array($medicationData)) {
            return;
        }

        foreach ($medicationData as $data) {
            $medication = new Medication();
            $medication->setName($data['name'])
                ->setCategory($data['category'])
                ->setForm($data['form'])
                ->setDosage($data['dosage'] ?? '')
                ->setPrice((string) ($data['price'] ?? '0'))
                ->setRequiresPrescription((bool) ($data['requiresPrescription'] ?? false))
                ->setIsReimbursed((bool) ($data['isReimbursed'] ?? false))
                ->setInsuranceCoverage((int) ($data['insuranceCoverage'] ?? 0))
                ->setIsActive((bool) ($data['isActive'] ?? true))
                ->setManufacturer($data['manufacturer'] ?? null)
                ->setDescription($data['description'] ?? null)
                ->setSideEffects($data['sideEffects'] ?? null)
                ->setContraindications($data['contraindications'] ?? null)
                ->setActiveIngredient($data['activeIngredient'] ?? null)
                ->setImage($data['image'] ?? null);

            if (!empty($data['posologie'])) {
                $medication->setPosologie($data['posologie']);
            }

            $manager->persist($medication);
        }
    }

    private function createSpecialities(ObjectManager $manager): array
    {
        $specialities = [];
        $specialityData = [
            ['name' => 'Cardiologie', 'description' => 'Spécialité médicale qui traite les maladies du cœur et des vaisseaux', 'iconUrl' => 'icons/cardiology.svg', 'sortOrder' => 1],
            ['name' => 'Dermatologie', 'description' => 'Spécialité médicale qui traite les maladies de la peau', 'iconUrl' => 'icons/dermatology.svg', 'sortOrder' => 2],
            ['name' => 'Médecine générale', 'description' => 'Médecine de premier recours et de soins primaires', 'iconUrl' => 'icons/general-medicine.svg', 'sortOrder' => 3],
            ['name' => 'Pédiatrie', 'description' => 'Spécialité médicale consacrée aux enfants', 'iconUrl' => 'icons/pediatrics.svg', 'sortOrder' => 4],
            ['name' => 'Gynécologie', 'description' => 'Spécialité médicale qui traite l\'appareil génital féminin', 'iconUrl' => 'icons/gynecology.svg', 'sortOrder' => 5]
        ];

        foreach ($specialityData as $data) {
            $speciality = new Speciality();
            $speciality->setName($data['name'])
                ->setDescription($data['description'])
                ->setIconUrl($data['iconUrl'])
                ->setSortOrder($data['sortOrder'])
                ->setIsActive(true);
            $manager->persist($speciality);
            $specialities[] = $speciality;
        }

        return $specialities;
    }

    private function createCities(ObjectManager $manager): array
    {
        $cities = [];
        $cityData = [
            ['name' => 'Bamako', 'region' => 'District de Bamako', 'latitude' => '12.6392', 'longitude' => '-8.0029', 'population' => 2500000],
            ['name' => 'Sikasso', 'region' => 'Sikasso', 'latitude' => '11.3177', 'longitude' => '-5.6719', 'population' => 225753],
            ['name' => 'Koutiala', 'region' => 'Sikasso', 'latitude' => '12.3914', 'longitude' => '-5.4631', 'population' => 137919],
            ['name' => 'Kayes', 'region' => 'Kayes', 'latitude' => '14.4467', 'longitude' => '-11.4446', 'population' => 127368],
            ['name' => 'Mopti', 'region' => 'Mopti', 'latitude' => '14.4951', 'longitude' => '-4.1974', 'population' => 120786]
        ];

        foreach ($cityData as $data) {
            $city = new City();
            $city->setName($data['name'])
                ->setRegion($data['region'])
                ->setCountry('Mali')
                ->setLatitude($data['latitude'])
                ->setLongitude($data['longitude'])
                ->setPopulation($data['population'])
                ->setIsActive(true);
            $manager->persist($city);
            $cities[] = $city;
        }

        return $cities;
    }

    private function createAppSettings(ObjectManager $manager): void
    {
        $settingsData = [
            ['key' => 'app_name', 'value' => 'Health App Mali', 'description' => 'Nom de l\'application', 'isPublic' => true, 'dataType' => DataType::STRING],
            ['key' => 'app_version', 'value' => '1.0.0', 'description' => 'Version de l\'application', 'isPublic' => true, 'dataType' => DataType::STRING],
            ['key' => 'default_consultation_fee', 'value' => '25000', 'description' => 'Tarif de consultation par défaut en FCFA', 'isPublic' => false, 'dataType' => DataType::INTEGER],
            ['key' => 'emergency_phone', 'value' => '+223 15 15', 'description' => 'Numéro d\'urgence national', 'isPublic' => true, 'dataType' => DataType::STRING],
            ['key' => 'maintenance_mode', 'value' => 'false', 'description' => 'Mode maintenance activé', 'isPublic' => false, 'dataType' => DataType::BOOLEAN]
        ];

        foreach ($settingsData as $data) {
            $setting = new AppSetting();
            $setting->setKey($data['key'])
                ->setValue($data['value'])
                ->setDescription($data['description'])
                ->setIsPublic($data['isPublic'])
                ->setDataType($data['dataType']);
            $manager->persist($setting);
        }
    }

    private function createReviews(ObjectManager $manager, array $users, array $doctors, array $establishments): void
    {
        $reviewsData = [
            [
                'reviewerIndex' => 4, // Sophie Leroy
                'revieweeType' => RevieweeType::DOCTOR,
                'revieweeIndex' => 0, // Dr. Martin
                'rating' => 5,
                'comment' => 'Excellent cardiologue, tres professionnel et a l\'ecoute.',
                'isAnonymous' => false,
                'isVerified' => true
            ],
            [
                'reviewerIndex' => 5, // Michel Moreau
                'revieweeType' => RevieweeType::ESTABLISHMENT,
                'revieweeIndex' => 0, // Hôpital Saint-Louis
                'rating' => 4,
                'comment' => 'Bon service, personnel competent mais temps d\'attente un peu long.',
                'isAnonymous' => false,
                'isVerified' => true
            ]
        ];

        foreach ($reviewsData as $data) {
            $review = new Review();
            $review->setReviewerId($users[$data['reviewerIndex']]->getId())
                ->setRevieweeType($data['revieweeType'])
                ->setRating($data['rating'])
                ->setComment($data['comment'])
                ->setIsAnonymous($data['isAnonymous'])
                ->setIsVerified($data['isVerified']);

            if ($data['revieweeType'] === RevieweeType::DOCTOR) {
                $review->setRevieweeId($doctors[$data['revieweeIndex']]->getId());
            } else {
                $review->setRevieweeId($establishments[$data['revieweeIndex']]->getId());
            }

            $manager->persist($review);
        }
    }

    private function createEmergencyContacts(ObjectManager $manager, array $users): void
    {
        $contactsData = [
            [
                'userIndex' => 4, // Sophie Leroy
                'name' => 'Pierre Leroy',
                'relationship' => 'Époux',
                'phone' => '+33123456800',
                'email' => 'pierre.leroy@email.com',
                'isPrimary' => true
            ],
            [
                'userIndex' => 5, // Michel Moreau
                'name' => 'Anne Moreau',
                'relationship' => 'Épouse',
                'phone' => '+33123456802',
                'email' => 'anne.moreau@email.com',
                'isPrimary' => true
            ]
        ];

        foreach ($contactsData as $data) {
            $contact = new EmergencyContact();
            $contact->setUserId($users[$data['userIndex']]->getId())
                ->setName($data['name'])
                ->setRelationship($data['relationship'])
                ->setPhone($data['phone'])
                ->setEmail($data['email'])
                ->setIsPrimary($data['isPrimary']);

            $manager->persist($contact);
        }
    }

    private function createHealthTips(ObjectManager $manager, array $users): void
    {
        $tipsData = [
            [
                'authorIndex' => 1, // Dr. Martin
                'category' => HealthTipCategory::NUTRITION,
                'title' => 'Les bienfaits des fruits et légumes locaux',
                'content' => 'Consommer des fruits et légumes de saison cultivés localement apporte de nombreux bénéfices pour la santé.',
                'summary' => 'Decouvrez pourquoi privilegier les produits locaux et de saison.',
                'tags' => ['nutrition', 'fruits', 'legumes'],
                'isFeatured' => true,
                'isPublished' => true
            ],
            [
                'authorIndex' => 2, // Dr. Dubois
                'category' => HealthTipCategory::PREVENTION,
                'title' => 'Protection solaire : conseils essentiels',
                'content' => 'La protection contre les rayons UV est cruciale pour prevenir le cancer de la peau.',
                'summary' => 'Comment bien se proteger du soleil au quotidien.',
                'tags' => ['soleil', 'protection', 'peau'],
                'isFeatured' => false,
                'isPublished' => true
            ]
        ];

        foreach ($tipsData as $data) {
            $tip = new HealthTip();
            $tip->setAuthorId($users[$data['authorIndex']]->getId())
                ->setCategory($data['category'])
                ->setTitle($data['title'])
                ->setContent($data['content'])
                ->setSummary($data['summary'])
                ->setTags($data['tags'])
                ->setIsFeatured($data['isFeatured'])
                ->setIsPublished($data['isPublished']);

            $manager->persist($tip);
        }
    }

    private function createMedicalHistories(ObjectManager $manager, array $users, array $doctors, array $appointments): void
    {
        $historyData = [
            [
                'patientIndex' => 4,
                'doctorIndex' => 0,
                'appointmentIndex' => 0,
                'category' => MedicalHistoryCategory::CONSULTATION,
                'title' => 'Consultation cardiologique de suivi',
                'description' => 'Contrôle annuel suite à antécédents familiaux.',
                'diagnosis' => 'Hypertension légère',
                'treatment' => 'Surveillance + ajustement de l\'alimentation',
                'medications' => [
                    ['name' => 'Lisinopril', 'dosage' => '10mg', 'frequency' => '1/jour']
                ],
                'attachments' => [
                    ['type' => 'ECG', 'url' => 'documents/ecg-001.pdf']
                ],
                'date' => '-10 days',
                'isPrivate' => false,
                'cost' => '80.00',
                'insuranceNumber' => 'INS-123456'
            ],
            [
                'patientIndex' => 5,
                'doctorIndex' => 1,
                'appointmentIndex' => 1,
                'category' => MedicalHistoryCategory::DIAGNOSIS,
                'title' => 'Diagnostic dermatologique',
                'description' => 'Examen d\'une eruption cutanée persistante.',
                'diagnosis' => 'Dermatite de contact',
                'treatment' => 'Creme hydratante + evitement allergenes',
                'medications' => [
                    ['name' => 'Crème hydratante', 'dosage' => '2x/jour']
                ],
                'attachments' => null,
                'date' => '-3 days',
                'isPrivate' => false,
                'cost' => '70.00',
                'insuranceNumber' => 'INS-654321'
            ],
            [
                'patientIndex' => 4,
                'doctorIndex' => 2,
                'appointmentIndex' => 2,
                'category' => MedicalHistoryCategory::LAB_TEST,
                'title' => 'Bilan sanguin general',
                'description' => 'Analyse biologique de routine.',
                'diagnosis' => 'Resultats dans les normes',
                'treatment' => null,
                'medications' => null,
                'attachments' => [
                    ['type' => 'LAB', 'url' => 'documents/lab-2024-001.pdf']
                ],
                'date' => '-1 month',
                'isPrivate' => true,
                'cost' => '45.00',
                'insuranceNumber' => 'INS-123456'
            ]
        ];

        foreach ($historyData as $data) {
            $history = new MedicalHistory();
            $history->setPatientId($users[$data['patientIndex']]->getId())
                ->setDoctorId($doctors[$data['doctorIndex']]->getId())
                ->setAppointmentId($appointments[$data['appointmentIndex']]->getId())
                ->setCategory($data['category'])
                ->setTitle($data['title'])
                ->setDescription($data['description'])
                ->setDiagnosis($data['diagnosis'])
                ->setTreatment($data['treatment'])
                ->setMedications($data['medications'])
                ->setAttachments($data['attachments'])
                ->setDate(new \DateTimeImmutable($data['date']))
                ->setIsPrivate($data['isPrivate'])
                ->setCost($data['cost'])
                ->setInsuranceNumber($data['insuranceNumber']);

            $manager->persist($history);
        }
    }

    private function createNotifications(ObjectManager $manager, array $users): void
    {
        $notificationsData = [
            [
                'userIndex' => 4,
                'type' => NotificationType::REMINDER,
                'title' => 'Rappel de rendez-vous',
                'message' => 'Votre rendez-vous est prévu demain à 10:00.',
                'data' => ['source' => 'appointment'],
                'isRead' => false,
                'priority' => NotificationPriority::NORMAL,
                'expiresAt' => '+2 days'
            ],
            [
                'userIndex' => 5,
                'type' => NotificationType::ALERT,
                'title' => 'Analyse disponible',
                'message' => 'Les résultats de votre analyse sont disponibles.',
                'data' => ['source' => 'lab'],
                'isRead' => true,
                'priority' => NotificationPriority::HIGH,
                'expiresAt' => '+7 days'
            ],
            [
                'userIndex' => 1,
                'type' => NotificationType::SYSTEM,
                'title' => 'Nouveau patient inscrit',
                'message' => 'Un nouveau patient a créé un compte.',
                'data' => null,
                'isRead' => true,
                'priority' => NotificationPriority::LOW,
                'expiresAt' => null
            ]
        ];

        foreach ($notificationsData as $data) {
            $notification = new Notification();
            $notification->setUserId($users[$data['userIndex']]->getId())
                ->setType($data['type'])
                ->setTitle($data['title'])
                ->setMessage($data['message'])
                ->setData($data['data'])
                ->setIsRead($data['isRead'])
                ->setPriority($data['priority']);

            if (!empty($data['expiresAt'])) {
                $notification->setExpiresAt(new \DateTimeImmutable($data['expiresAt']));
            }

            $manager->persist($notification);
        }
    }
}
