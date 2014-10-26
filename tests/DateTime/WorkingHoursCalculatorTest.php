<?php

class WorkingHoursCalculatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * A Data provider for the test
     * @return array
     */
    public function provider()
    {
        return $testData = array(
            array(
                '2014-10-24 09:00:00',
                '2014-10-24 10:00:00',
                1 // expected interval in working hours 9:00 to 17:00
            ),
            array(
                '2014-10-24 09:00:00',
                '2014-10-24 17:00:00',
                8
            ),
            array(
                '2014-10-24 09:00:00',
                '2014-10-24 18:00:00',
                9
            ),
            array(
                '2014-10-24 09:00:00',
                '2014-10-27 09:00:00',
                8
            ),
            array(
                '2014-10-24 09:00:00',
                '2014-10-27 08:30:00',
                7.5
            ),
            array(
                '2014-10-27 08:30:00',
                '2014-10-27 09:00:00',
                0.5
            ),
            array(
                '2014-10-26 08:00:00',
                '2014-10-27 08:30:00',
                0.5
            ),
            array(
                '2014-10-26 09:00:00',
                '2014-10-27 08:30:00',
                0
            )
        );
    }

    /**
     * @dataProvider provider
     */
    public function testCalculation($fromDate, $toDate, $expectedResult)
    {
        $this->check($fromDate, $toDate, $expectedResult);
    }

    public function badInputProvider()
    {
        return array(
            array(
                '2014-10-27 09:00:00',
                '2014-10-27 08:30:00'
            )
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider badInputProvider
     * @param string $fromDate
     * @param string $toDate
     */
    public function testBadInput($fromDate, $toDate)
    {
        $this->check($fromDate, $toDate, 0); // note the @expectedException annotation
    }

    private function check($fromDate, $toDate, $expectedResult)
    {
        $calculator = new \DateTime\WorkingHoursCalculator();
        $this->assertEquals($expectedResult, $calculator->getWorkingHours(strtotime($fromDate), strtotime($toDate)));
    }

}