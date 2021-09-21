<?php
class TimeZone
{

    public static function getTimeZoneArray()
    {
        $regions = array(
            'Africa' => DateTimeZone::AFRICA,
            'America' => DateTimeZone::AMERICA,
            'Antarctica' => DateTimeZone::ANTARCTICA,
            'Arctic' => DateTimeZone::ARCTIC,
            'Aisa' => DateTimeZone::ASIA,
            'Atlantic' => DateTimeZone::ATLANTIC,
            'Australia' => DateTimeZone::AUSTRALIA,
            'Europe' => DateTimeZone::EUROPE,
            'Indian' => DateTimeZone::INDIAN,
            'Pacific' => DateTimeZone::PACIFIC
        );

        $tzones = array();
        foreach ($regions as $mask) {
            $zones = DateTimeZone::listIdentifiers($mask);
            $zones = self::prepareZones($zones);

            foreach ($zones as $zone) {
                $timeZone = $zone['time_zone'];
 
                if ($timeZone) {
                    $tzones[] = $timeZone;
                }
            }
        }

        return $tzones;
    }

    public static function getTimeZoneSelect($selectedZone = NULL)
    {
        $regions = array(
            'Africa' => DateTimeZone::AFRICA,
            'America' => DateTimeZone::AMERICA,
            'Antarctica' => DateTimeZone::ANTARCTICA,
            'Arctic' => DateTimeZone::ARCTIC,
            'Aisa' => DateTimeZone::ASIA,
            'Atlantic' => DateTimeZone::ATLANTIC,
            'Australia' => DateTimeZone::AUSTRALIA,
            'Europe' => DateTimeZone::EUROPE,
            'Indian' => DateTimeZone::INDIAN,
            'Pacific' => DateTimeZone::PACIFIC
        );
 
        $structure = '';
        $structure .= '<option value="">- Не указано -</option>';
 
        foreach ($regions as $mask) {
            $zones = DateTimeZone::listIdentifiers($mask);
            $zones = self::prepareZones($zones);
 
            foreach ($zones as $zone) {
                $continent = $zone['continent'];
                $city = $zone['city'];
                $subcity = $zone['subcity'];
                $p = $zone['p'];
                $timeZone = $zone['time_zone'];
 
                if (!isset($selectContinent)) {
                    $structure .= '<optgroup label="'.$continent.'">';
                }
                elseif ($selectContinent != $continent) {
                    $structure .= '</optgroup><optgroup label="'.$continent.'">';
                }
 
                if ($city) {
                    if ($subcity) {
                        $city = $city . '/'. $subcity;
                    }
                    $date = new DateTime();
                    $date->setTimezone(new DateTimeZone($timeZone));
                    $structure .= "<option ".(($timeZone == $selectedZone) ? 'selected="selected "':'') . " value=\"".($timeZone)."\">(".$p. " UTC) " .str_replace('_',' ',$city)." - " . $date->format('Y-m-d H:i:s') . "</option>";
                }
 
                $selectContinent = $continent;
            }
        }
 
        $structure .= '</optgroup>';
 
        return $structure;
    }
 
    private static function prepareZones(array $timeZones)
    {
        $list = array();
        foreach ($timeZones as $zone) {
            $time = new DateTime(NULL, new DateTimeZone($zone));
            $p = $time->format('P');
            if ($p > 13) {
                // continue; // Зачем :ч
            }
            $parts = explode('/', $zone);
 
            $list[$time->format('P')][] = array(
                'time_zone' => $zone,
                'continent' => isset($parts[0]) ? $parts[0] : '',
                'city' => isset($parts[1]) ? $parts[1] : '',
                'subcity' => isset($parts[2]) ? $parts[2] : '',
                'p' => $p,
            );
        }
 
        ksort($list, SORT_NUMERIC);
 
        $zones = array();
        foreach ($list as $grouped) {
            $zones = array_merge($zones, $grouped);
        }
 
        return $zones;
    }
}