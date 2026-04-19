<?php

namespace Database\Seeders;

use App\Models\SectionsInfo;
use Illuminate\Database\Seeder;

class AiSectionSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            1 => [
                'ai_mode' => 'voice',
                'ai_hint' => '<p><strong>Please mention the following:</strong></p>
<ul>
  <li>Patient full name</li>
  <li>National ID number</li>
  <li>Phone number and email address</li>
  <li>Age and gender</li>
  <li>Governorate and residency type (rural or urban)</li>
  <li>Marital status and number of children</li>
  <li>Educational level and occupation</li>
  <li>Hospital name and department</li>
  <li>Who collected the data (patient, family member, etc.)</li>
  <li>Special habits: smoking, shisha, alcohol, or drug use</li>
  <li>Does the patient have Diabetes Mellitus? If yes, for how many years?</li>
  <li>Does the patient have Hypertension? If yes, for how many years?</li>
  <li>Black race (yes or no)</li>
  <li>Any other relevant medical history</li>
</ul>',
            ],
            2 => [
                'ai_mode' => 'voice',
                'ai_hint' => '<p><strong>Please mention the following:</strong></p>
<ul>
  <li>Where was the patient first seen? (Outpatient clinic, Emergency room, or directly admitted)</li>
  <li>Date of admission</li>
  <li>Main complaints — you can mention more than one:
    <ul>
      <li>Oliguria or anuria</li>
      <li>Change in urine color</li>
      <li>Painful urination</li>
      <li>Swelling of face, legs, or whole body</li>
      <li>Fatigue or tiredness</li>
      <li>Confusion</li>
      <li>Chest pain or shortness of breath</li>
      <li>Any other complaint</li>
    </ul>
  </li>
  <li>Urine output status: normal, oliguria/anuria, polyuria, or unknown</li>
  <li>Provisional diagnosis: AKI, AKI on CKD, GN, or unclear</li>
</ul>',
            ],
            3 => [
                'ai_mode' => 'voice',
                'ai_hint' => '<p><strong>Please mention the following:</strong></p>
<ul>
  <li>Main cause of AKI: pre-renal, intrinsic renal, post-renal, or unclear</li>
  <li>If <strong>pre-renal</strong>, specify the cause:
    <ul>
      <li>Volume depletion due to hemorrhage, vomiting, diarrhea, burns, or diuretics</li>
      <li>Edematous state due to heart failure, liver cirrhosis, or nephrotic syndrome</li>
      <li>Sepsis or other hemodynamic cause</li>
    </ul>
  </li>
  <li>If <strong>intrinsic renal</strong>, specify the cause:
    <ul>
      <li>Glomerular disease: GN, TTP, HUS, or other</li>
      <li>Tubular injury: ischemic ATN or toxic ATN</li>
      <li>Acute interstitial nephritis: drug-induced or infection-related</li>
      <li>Vascular cause</li>
    </ul>
  </li>
  <li>If <strong>post-renal</strong>, specify the cause:
    <ul>
      <li>Calculus, blood clot, papillary necrosis</li>
      <li>Urethral stricture, prostatic hypertrophy or malignancy</li>
      <li>Bladder tumor, radiation fibrosis, pelvic malignancy</li>
    </ul>
  </li>
  <li>Any other causes not listed above</li>
</ul>',
            ],
            4 => [
                'ai_mode' => 'voice',
                'ai_hint' => '<p><strong>Please answer yes or no for each of the following:</strong></p>
<ul>
  <li>History of Chronic Kidney Disease (CKD)</li>
  <li>Past history of AKI</li>
  <li>History of cardiac failure</li>
  <li>History of liver cirrhosis (LCF)</li>
  <li>History of neurological impairment or disability</li>
  <li>History of sepsis</li>
  <li>Recent use of iodinated contrast media</li>
  <li>Current or recent use of nephrotoxic drugs — if yes, specify which:
    <ul>
      <li>NSAIDs, ACE inhibitors or ARBs, Aminoglycosides, Diuretics, Drug addiction, or other</li>
    </ul>
  </li>
  <li>History of hypovolemia</li>
  <li>History of malignancy</li>
  <li>History of trauma</li>
  <li>History of autoimmune disease</li>
  <li>Any other risk factors</li>
</ul>',
            ],
            5 => [
                'ai_mode' => 'voice',
                'ai_hint' => '<p><strong>Please mention the following:</strong></p>
<ul>
  <li><strong>Vital signs:</strong> heart rate, respiratory rate, SBP, DBP, oxygen saturation, temperature</li>
  <li>GCS score and AVPU level (Alert, Verbal, Pain, or Unresponsive)</li>
  <li>Height (cm) and weight (kg)</li>
  <li>Urine output: ml/hour, total in first 6 hours, total in first 24 hours</li>
  <li><strong>Examination findings:</strong>
    <ul>
      <li>Skin: normal, petechiae, purpura, ecchymosis, butterfly rash, or other</li>
      <li>Eyes: normal, jaundice, pallor, uveitis, or other</li>
      <li>Ears: normal, hearing loss, or other</li>
      <li>Cardiac: normal, pericardial rub, murmur, abnormal sounds, or other</li>
      <li>Internal jugular vein: congested or non-congested</li>
      <li>Chest: normal, crepitations, pleural rub, reduced air entry, wheezes, or other</li>
      <li>Abdomen: loin pain, ascites, palpable kidney or bladder, bruit, or other</li>
    </ul>
  </li>
  <li>Any other important examination findings</li>
</ul>',
            ],
            6 => [
                'ai_mode' => 'image',
                'ai_hint' => '<p><strong>Please upload a clear image of the lab report or radiology result. Make sure it includes:</strong></p>
<ul>
  <li><strong>Kidney function:</strong> creatinine (admission, day 2–10), urea, BUN</li>
  <li><strong>Blood gases:</strong> pH, HCO3, pCO2</li>
  <li><strong>Electrolytes:</strong> serum sodium, potassium, calcium, phosphorus</li>
  <li><strong>Liver panel:</strong> SGOT, SGPT, bilirubin (total and direct), albumin, alkaline phosphatase</li>
  <li><strong>Coagulation:</strong> PT, PTT, INR</li>
  <li><strong>CBC:</strong> hemoglobin, WBCs, platelets, neutrophils, lymphocytes, monocytes, eosinophil, basophil</li>
  <li><strong>Other labs:</strong> CRP, HbA1c, LDH, uric acid, random blood glucose, LDL, HDL, total cholesterol, triglycerides, parathormone</li>
  <li><strong>Serology:</strong> HCV Ab, HBs Ag, HIV Ab</li>
  <li><strong>Urine analysis:</strong> specific gravity, clarity, epithelial cells, crystals, casts, WBCs, RBCs, proteinuria, ACR, 24-hour urine protein</li>
  <li><strong>Radiology:</strong> renal ultrasound findings, CT abdomen summary, CT chest summary</li>
  <li><strong>Other:</strong> renal biopsy diagnosis, ECHO report summary</li>
</ul>',
            ],
            7 => [
                'ai_mode' => 'voice',
                'ai_hint' => '<p><strong>Please mention the following:</strong></p>
<ul>
  <li>Medical decision: admission, referral to higher center, home discharge, or other</li>
  <li>Did the patient receive dialysis?
    <ul>
      <li>If yes — modality: HD, PD, CRRT, or HDF</li>
      <li>Indication: uremic encephalopathy, pericarditis, hyperkalemia, acidosis, pulmonary edema, or other</li>
      <li>Number of dialysis sessions</li>
      <li>Vascular access type: temporary catheter, permanent catheter, or AVF</li>
      <li>Site of access: femoral, jugular, or subclavian</li>
    </ul>
  </li>
  <li>Other management given:
    <ul>
      <li>Fluid resuscitation or restriction, diuretics, hyperkalemia management</li>
      <li>Antibiotics, blood transfusion, mechanical ventilation, hyperglycemia control</li>
      <li>Any other treatment</li>
    </ul>
  </li>
  <li>Did the patient receive immunosuppressive drugs? If yes, specify type</li>
  <li>If the patient has HRS, classify: HRS-AKI, HRS-AKD, HRS-NAKI, or HRS-CKD</li>
</ul>',
            ],
            8 => [
                'ai_mode' => 'voice',
                'ai_hint' => '<p><strong>Please mention the following:</strong></p>
<ul>
  <li>Outcome: survivor or death</li>
  <li>Duration of admission in days</li>
  <li>Final status: no improvement (on or off dialysis), partial improvement, or complete improvement</li>
  <li><strong>Discharge labs:</strong>
    <ul>
      <li>Creatinine, urea, BUN, serum sodium, potassium</li>
      <li>pH, HCO3, pCO2</li>
      <li>SGOT, SGPT, bilirubin, albumin</li>
      <li>Hemoglobin, WBCs, platelets, neutrophils, lymphocytes, monocytes, eosinophil, basophil</li>
      <li>CRP</li>
    </ul>
  </li>
  <li><strong>Urine at discharge:</strong> specific gravity, clarity, epithelial cells, crystals, casts, WBCs, RBCs, proteinuria</li>
  <li>Urine output in the last 6 hours before discharge</li>
  <li>For cardiac patients: ejection fraction on discharge and ECHO summary</li>
  <li>Any other outcome details</li>
</ul>',
            ],
            9 => [
                'ai_mode' => null,
                'ai_hint' => null,
            ],
            10 => [
                'ai_mode' => 'voice',
                'ai_hint' => '<p><strong>Please mention the following:</strong></p>
<ul>
  <li>Type of cardiac surgery performed (e.g. CABG, valve replacement, aortic surgery)</li>
  <li>Type of cardiac disease (e.g. IHD, mitral stenosis, aortic aneurysm)</li>
  <li><strong>Pre-operative labs:</strong>
    <ul>
      <li>SBP and DBP, WBCs, hemoglobin, platelets, creatinine, INR, albumin, bilirubin, ALT, AST, troponin</li>
      <li>Ejection fraction (%) and Echo summary</li>
      <li>Urine: pus cells, RBCs, proteinuria, casts</li>
      <li>pH, HCO3, pCO2</li>
    </ul>
  </li>
  <li><strong>Intra-operative data:</strong>
    <ul>
      <li>CPB duration and cross-clamp time (minutes)</li>
      <li>Core temperature: lowest and highest</li>
      <li>Minimum and maximum flow (L/min), PO2 (mmHg), and pressure (mmHg)</li>
      <li>Serum lactate during and after surgery</li>
      <li>Cardioplegia types used (up to 4)</li>
      <li>Any abnormal events during surgery</li>
    </ul>
  </li>
  <li><strong>Post-operative:</strong> immediate SBP, DBP, pH, HCO3, pCO2</li>
  <li>Blood transfusion: components received (RBCs, plasma, platelets, whole blood) and number of units each</li>
</ul>',
            ],
            11 => [
                'ai_mode' => 'voice',
                'ai_hint' => '<p><strong>Please mention the following:</strong></p>
<ul>
  <li>Date of first presentation</li>
  <li>Gravidity (number of pregnancies) and parity (number of deliveries)</li>
  <li>State at presentation: pregnant or postpartum</li>
  <li>Where did the patient receive medical care? (home, outpatient clinic, hospital ward, or ICU)</li>
  <li>Did the patient receive antenatal care? (yes or no)</li>
  <li>Did the patient have preeclampsia or eclampsia? (antepartum or postpartum)</li>
  <li>Past history of preeclampsia or eclampsia? (yes or no)</li>
  <li>Did the patient have obstetric hemorrhages? (antepartum or postpartum)</li>
  <li>Any other organ failure at presentation? If yes, specify</li>
  <li>Past history of similar attacks of pregnancy-related AKI? (yes or no)</li>
  <li>If postpartum and delivered by Cesarean section — were there surgical complications?</li>
  <li>Was the patient oliguric at presentation? (yes or no)</li>
  <li>24-hour urinary protein or albumin-to-creatinine ratio on last follow-up (mg/day)</li>
  <li>Protein on dipstick on last follow-up (number of + signs)</li>
  <li>Maternal outcome at last follow-up: alive or dead</li>
  <li>Fetal outcome: live full term, live preterm, stillbirth, abortion, or missed follow-up</li>
  <li>Was neonatal ICU available for preterm babies? (yes or no)</li>
</ul>',
            ],
            12 => [
                'ai_mode' => null,
                'ai_hint' => null,
            ],
            13 => [
                'ai_mode' => null,
                'ai_hint' => null,
            ],
        ];

        foreach ($sections as $id => $data) {
            SectionsInfo::where('id', $id)->update($data);
        }

        $this->command->info('ai_mode and ai_hint updated for all sections.');
    }
}
