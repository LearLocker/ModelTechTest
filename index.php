<?php

/**
 * @throws Exception
 */
function getCarList(string $fileName): array
{
    $result = [];
    $headline = null;

    if (($h = fopen("{$fileName}", "r")) !== FALSE) {
        while (($row = fgetcsv($h, 1000, ";")) !== FALSE) {
            if ($headline === null) {
                $headline = $row;
                continue;
            }

            if (count($headline) !== count($row))
                continue;

            $data = array_combine($headline, $row);

            if (!validate($data))
                continue;

            switch ($data['car_type']) {
                case 'car':
                    $result[] = new Car(
                        CarTypeEnum::CAR,
                        $data['photo_file_name'],
                        $data['brand'],
                        floatval($data['carrying']),
                        intval($data['passenger_seats_count']));
                    break;

                case 'truck':
                    $result[] = new Truck(
                        CarTypeEnum::TRUCK,
                        $data['photo_file_name'],
                        $data['brand'],
                        floatval($data['carrying']),
                        $data['body_whl']);
                    break;

                case 'spec_machine':
                    $result[] = new SpecMachine(
                        CarTypeEnum::SPEC_MACHINE,
                        $data['photo_file_name'],
                        $data['brand'],
                        floatval($data['carrying']),
                        $data['extra']);
                    break;
            }
        }

        fclose($h);
    }

    return $result;
}

function validatePhotoName($photoName): bool
{
    return (bool)pathinfo($photoName, PATHINFO_EXTENSION);
}

function validateBodyWhl($bodyWhl): bool
{
    if (empty($bodyWhl))
        return true;

    if (is_string($bodyWhl)) {
        return !empty(array_filter(
            explode('x', $bodyWhl),
            fn(string $value) => is_numeric($value),
        ));
    }

    return false;
}

function validate(array $data): bool
{
    $result = true;

    if (!CarTypeEnum::tryFrom($data['car_type']) ||
        !validatePhotoName($data['photo_file_name']) ||
        !is_numeric($data['carrying']) ||
        empty($data['brand'])
    ) {
        $result = false;
    }

    return $result && match ($data['car_type']) {
            'truck' => validateBodyWhl($data['body_whl']),
            'car' => is_numeric($data['passenger_seats_count']),
            'spec_machine' => empty($data['extra'])
    };
}

enum CarTypeEnum: string
{
    case CAR = 'car';
    case TRUCK = 'truck';
    case SPEC_MACHINE = 'specMachine';
}

class BaseCar
{
    private CarTypeEnum $carType;
    private string $photoFileName;
    private string $brand;
    private float $carrying;

    public function __construct($carType, $photoFileName, $brand, $carrying)
    {
        $this->carType = $carType;
        $this->photoFileName = $photoFileName;
        $this->brand = $brand;
        $this->carrying = $carrying;
    }

    public function getPhotoFileExt(): array
    {
        return [pathinfo($this->photoFileName, PATHINFO_EXTENSION), imagecreatefromstring(file_get_contents($this->photoFileName))];
    }
}

class Car extends BaseCar
{
    private int $passengerSeatsCount;

    public function __construct($carType, $photoFileName, $brand, $carrying, $passengerSeatsCount)
    {
        $this->passengerSeatsCount = $passengerSeatsCount;

        parent::__construct($carType, $photoFileName, $brand, $carrying);
    }
}

class Truck extends BaseCar
{
    private float $bodyLength = .0;
    private float $bodyWidth = .0;
    private float $bodyHeight = .0;

    public function __construct($carType, $photoFileName, $brand, $carrying, $volume)
    {
        if (!empty($volume)) {
            list($this->bodyLength, $this->bodyWidth, $this->bodyHeight) = array_map(
                fn(string $value) => $value === '' ? 0. : floatval($value),
                explode('x', $volume)
            );
        }

        parent::__construct($carType, $photoFileName, $brand, $carrying);
    }

    public function getBodyVolume(): float
    {
        return $this->bodyLength * $this->bodyWidth * $this->bodyHeight;
    }
}

class SpecMachine extends BaseCar
{
    private string $extra;

    public function __construct($carType, $photoFileName, $brand, $carrying, $extra)
    {
        $this->extra = $extra;

        parent::__construct($carType, $photoFileName, $brand, $carrying);
    }
}


try {
    $result = getCarList('input.csv');
    echo var_export($result, true) . "\n";
} catch (Exception $e) {
    throw new Exception($e);
}

