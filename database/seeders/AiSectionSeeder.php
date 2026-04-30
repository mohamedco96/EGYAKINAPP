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
                'ai_voice_time' => 120,
                'ai_hint' => '<p><strong>Please mention the following patient information:</strong></p>
<ul>
  <li>Patient full name, National ID number, phone number, and email address</li>
  <li>Age and gender (male or female)</li>
  <li>Governorate and residency type: urban or rural</li>
  <li>Marital status (married, unmarried, widow, or divorced) and number of children</li>
  <li>Educational level (none, primary, secondary, college, or postgraduate) and occupation (no job, retired, sick leave, or has a job)</li>
  <li>Hospital name and department</li>
  <li>Who collected the data: the patient himself or a relative</li>
  <li>Special habits: cigarette smoking, shisha, drug addiction, or none</li>
  <li>Does the patient have Diabetes Mellitus? If yes, for how many years?</li>
  <li>Does the patient have Hypertension? If yes, for how many years?</li>
  <li>Is the patient of black race? (yes or no)</li>
  <li>Any other relevant medical history</li>
</ul>',
            ],
            2 => [
                'ai_mode' => 'voice',
                'ai_voice_time' => 120,
                'ai_hint' => '<p><strong>Please mention the following:</strong></p>
<ul>
  <li>Where was the patient first seen? (outpatient clinic, emergency room, or directly admitted)</li>
  <li>Date of admission</li>
  <li>Main complaints — mention all that apply:
    <ul>
      <li>Oliguria or anuria, change in urine color, painful urination</li>
      <li>Swelling (face, legs, or whole body), fatigue, confusion</li>
      <li>Chest pain, shortness of breath, nausea or vomiting</li>
      <li>Seizures, fever, loin pain, jaundice, abdominal pain, ascites</li>
      <li>Accidentally discovered, or any other complaint</li>
    </ul>
  </li>
  <li>Urine output status: normal, oliguria/anuria, polyuria, or unknown</li>
  <li>Provisional diagnosis: AKI, AKI on top of CKD, GN, unclear, or other</li>
</ul>',
            ],
            3 => [
                'ai_mode' => 'voice',
                'ai_voice_time' => 120,
                'ai_hint' => '<p><strong>Please mention the cause of AKI and specify details:</strong></p>
<ul>
  <li>Main cause: pre-renal, intrinsic renal, post-renal, or unclear</li>
  <li>If <strong>pre-renal</strong>, specify:
    <ul>
      <li>Volume depletion: hemorrhage, vomiting, diarrhea, burns, diuresis, or hematemesis/melena</li>
      <li>Edematous state: heart failure, liver cirrhosis, or nephrotic syndrome</li>
      <li>Hemodynamic: septic shock, cardiogenic shock, anaphylactic shock, or unspecified shock</li>
      <li>Drugs: NSAIDs or ACE inhibitors/ARBs</li>
      <li>Vascular: aortic aneurysm, renal artery stenosis, or hepatorenal syndrome</li>
      <li>Spontaneous bacterial peritonitis (SBP) or other pre-renal cause</li>
    </ul>
  </li>
  <li>If <strong>intrinsic renal</strong>, specify:
    <ul>
      <li>Glomerular: GN, TTP, HUS, lupus nephritis, eclampsia, or vasculitis</li>
      <li>Tubular: ischemic ATN or toxic ATN</li>
      <li>Interstitial nephritis: drug-induced or infection-related</li>
      <li>Vascular: malignant hypertension, cholesterol emboli, renal artery or vein disease</li>
    </ul>
  </li>
  <li>If <strong>post-renal</strong>, specify:
    <ul>
      <li>Calculus, blood clot, or papillary necrosis</li>
      <li>Urethral stricture, prostatic hypertrophy, or prostatic malignancy</li>
      <li>Bladder tumor, radiation fibrosis, pelvic malignancy, or retroperitoneal fibrosis</li>
    </ul>
  </li>
  <li>Any other cause not listed above</li>
</ul>',
            ],
            4 => [
                'ai_mode' => 'voice',
                'ai_voice_time' => 120,
                'ai_hint' => '<p><strong>Please answer yes or no for each risk factor:</strong></p>
<ul>
  <li>History of Chronic Kidney Disease (CKD)</li>
  <li>Past history of AKI</li>
  <li>History of cardiac failure</li>
  <li>History of liver cirrhosis (LCF)</li>
  <li>History of neurological impairment or disability</li>
  <li>History of sepsis</li>
  <li>Recent use of iodinated contrast media</li>
  <li>Current or recent use of nephrotoxic drugs — if yes, specify:
    <ul>
      <li>NSAIDs, ACE inhibitors or ARBs, aminoglycosides, diuretics, drug addiction, or other (specify name)</li>
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
                'ai_voice_time' => 120,
                'ai_hint' => '<p><strong>Please mention the following assessment findings:</strong></p>
<ul>
  <li><strong>Vital signs:</strong> heart rate/min, respiratory rate/min, SBP, DBP, oxygen saturation (%), temperature</li>
  <li>GCS score and AVPU level (alert, verbal, pain, or unresponsive)</li>
  <li>Height (cm) and weight (kg)</li>
  <li>Urine output: ml/hour, total in first 6 hours, total in first 24 hours</li>
  <li><strong>Examination findings:</strong>
    <ul>
      <li>Skin: normal, petechiae/purpura, ecchymosis, livedo reticularis, digital ischemia, butterfly rash, palpable purpura, vasculitis, track marks, oedema, dehydration signs, jaundice, or other</li>
      <li>Eyes: normal, jaundice, pallor, uveitis, or other</li>
      <li>Ears: normal, hearing loss, or other</li>
      <li>Cardiac: normal, pericardial rub, murmur, abnormal heart sounds, irregular rhythm, or other</li>
      <li>Internal jugular vein: congested or non-congested</li>
      <li>Chest: normal, coarse/fine crepitations, pleural rub, reduced air entry, wheezes, or other</li>
      <li>Abdomen: loin pain, ascites, palpable kidney, palpable urinary bladder, bruit, or other</li>
    </ul>
  </li>
  <li>Any other important examination findings</li>
</ul>',
            ],
            6 => [
                'ai_mode' => 'image',
                'ai_voice_time' => null,
                'ai_hint' => '<p><strong>Please upload a clear image of the lab report or radiology result containing:</strong></p>
<ul>
  <li><strong>Kidney function:</strong> creatinine on admission, days 2–10; urea, BUN</li>
  <li><strong>Electrolytes &amp; blood gases:</strong> serum Na, K, Ca, phosphorus; pH, HCO3, pCO2</li>
  <li><strong>Liver panel:</strong> SGOT, SGPT, total &amp; direct bilirubin, albumin, alkaline phosphatase</li>
  <li><strong>Coagulation:</strong> PT, PTT, INR</li>
  <li><strong>CBC:</strong> hemoglobin, WBCs, platelets, neutrophils, lymphocytes, monocytes, eosinophils, basophils</li>
  <li><strong>Other labs:</strong> CRP, HbA1c, LDH, uric acid, random blood glucose, LDL, HDL, total cholesterol, triglycerides, parathormone</li>
  <li><strong>Serology:</strong> HCV Ab, HBs Ag, HIV Ab</li>
  <li><strong>Urine analysis:</strong> specific gravity, clarity, epithelial cells, crystals, casts, WBCs, RBCs, proteinuria, ACR, 24-hour urine protein</li>
  <li><strong>Radiology:</strong> renal ultrasound findings, CT abdomen summary, CT chest summary</li>
  <li><strong>Other:</strong> renal biopsy diagnosis (TMA, ATN, cortical necrosis, GN, interstitial nephritis), ECHO report summary</li>
</ul>',
            ],
            7 => [
                'ai_mode' => 'voice',
                'ai_voice_time' => 120,
                'ai_hint' => '<p><strong>Please mention the following management details:</strong></p>
<ul>
  <li>Medical decision: admission, referral to higher center, home discharge, or other</li>
  <li>Did the patient receive dialysis? (yes or no)
    <ul>
      <li>If yes — modality: HD, PD, CRRT, HDF, or other</li>
      <li>Indication: uremic encephalopathy, pericarditis, life-threatening hyperkalemia, refractory acidosis, pulmonary edema, or other</li>
      <li>Number of dialysis sessions</li>
      <li>Vascular access: temporary catheter, permanent catheter, AVF, or other</li>
      <li>Site of access: femoral, jugular, subclavian, or other</li>
    </ul>
  </li>
  <li>Other lines of management (mention all that apply):
    <ul>
      <li>IV fluids, fluid restriction, diuretics, hyperkalemia management, antihypertensives</li>
      <li>Antibiotics, blood transfusion, mechanical ventilation, hyperglycemia control, magnesium sulphate</li>
    </ul>
  </li>
  <li>Did the patient receive immunosuppressive drugs? If yes, specify type (e.g. corticosteroids, azathioprine, cyclosporine, tacrolimus, MMF, biologics)</li>
  <li>If the patient has hepatorenal syndrome, classify: HRS-AKI, HRS-AKD, HRS-NAKI, or HRS-CKD</li>
</ul>',
            ],
            8 => [
                'ai_mode' => 'voice',
                'ai_voice_time' => 120,
                'ai_hint' => '<p><strong>Please mention the following outcome details:</strong></p>
<ul>
  <li>Patient outcome: survivor or death</li>
  <li>Duration of admission in days</li>
  <li>Final status: no improvement (not on dialysis), no improvement (on dialysis), partial improvement, or complete improvement (≥90% creatinine improvement)</li>
  <li><strong>Discharge labs:</strong>
    <ul>
      <li>Creatinine, urea, BUN, serum sodium, potassium</li>
      <li>pH, HCO3, pCO2</li>
      <li>SGOT, SGPT, bilirubin, albumin</li>
      <li>Hemoglobin, WBCs, platelets, neutrophils, lymphocytes, monocytes, eosinophils, basophils, CRP</li>
    </ul>
  </li>
  <li><strong>Urine at discharge:</strong> specific gravity, clarity, epithelial cells, crystals, casts, WBCs, RBCs, proteinuria</li>
  <li>Urine output in the last 6 hours before discharge (ml)</li>
  <li>For cardiac surgery patients: ejection fraction (%) and ECHO summary on discharge</li>
</ul>',
            ],
            9 => [
                'ai_mode' => null,
                'ai_voice_time' => null,
                'ai_hint' => null,
            ],
            10 => [
                'ai_mode' => 'voice',
                'ai_voice_time' => 120,
                'ai_hint' => '<p><strong>Please mention the following for cardiac surgery patients:</strong></p>
<ul>
  <li>Type of surgery: CABG, mitral valve replacement/repair, aortic valve replacement/repair, double valve replacement, Bentall operation, aortic surgery, myxoma removal, Ozaki procedure, or other</li>
  <li>Type of cardiac disease: MS, MR, AS, AR, IHD, aortic aneurysm, myxoma, or other</li>
  <li><strong>Pre-operative data:</strong>
    <ul>
      <li>SBP and DBP, WBCs, hemoglobin, platelets, creatinine, INR, albumin, bilirubin, ALT, AST, troponin</li>
      <li>Ejection fraction (%) and Echo summary</li>
      <li>Urine: pus cells, RBCs, proteinuria, casts (granular, hyaline, waxy, RBC casts, WBC casts, or none)</li>
      <li>pH, HCO3, pCO2</li>
    </ul>
  </li>
  <li><strong>Intra-operative data:</strong>
    <ul>
      <li>CPB duration (min) and cross-clamp time (min)</li>
      <li>Core temperature: lowest and highest (°C)</li>
      <li>Minimum and maximum flow (L/min), PO2 (mmHg), and pressure (mmHg)</li>
      <li>Serum lactate during surgery and after surgery</li>
      <li>Cardioplegia types used (up to 4 types)</li>
      <li>Any abnormal events during surgery? (yes or no — if yes, specify)</li>
    </ul>
  </li>
  <li><strong>Post-operative:</strong> immediate SBP, DBP, pH, HCO3, pCO2</li>
  <li>Blood transfusion: did the patient receive blood components? If yes — RBCs, plasma, platelets, or whole blood — and how many units of each</li>
</ul>',
            ],
            11 => [
                'ai_mode' => 'voice',
                'ai_voice_time' => 120,
                'ai_hint' => '<p><strong>Please mention the following for obstetric patients:</strong></p>
<ul>
  <li>Date of first presentation</li>
  <li>Gravidity (total number of pregnancies) and parity (number of deliveries)</li>
  <li>State at presentation: pregnant or postpartum</li>
  <li>Where did the patient receive medical care? (home, outpatient clinic, hospital ward, or hospital ICU)</li>
  <li>Did the patient receive antenatal care? (yes or no)</li>
  <li>Did the patient have preeclampsia or eclampsia? (no, yes antepartum, or yes postpartum)</li>
  <li>Past history of preeclampsia or eclampsia? (no, yes antepartum, or yes postpartum)</li>
  <li>Did the patient have obstetric hemorrhages? (no, yes antepartum, or yes postpartum)</li>
  <li>Any other organ failure at presentation? If yes, specify</li>
  <li>Past history of similar attacks of pregnancy-related AKI? (yes or no)</li>
  <li>For postpartum patients delivered by Caesarean section — were there any surgical complications? (yes or no)</li>
  <li>Was the patient oliguric at presentation? (yes or no)</li>
  <li>24-hour urinary protein or albumin-to-creatinine ratio on last follow-up (mg/day)</li>
  <li>Protein on dipstick on last follow-up (unavailable, nil, +, ++, +++, or ++++ and more)</li>
  <li>Maternal outcome at last follow-up: alive or dead</li>
  <li>Fetal outcome: live full term, live preterm, IUFD/stillbirth, abortion, or missed follow-up</li>
  <li>Was neonatal ICU immediately available for preterm babies? (yes, no, or other)</li>
</ul>',
            ],
            12 => [
                'ai_mode' => null,
                'ai_voice_time' => null,
                'ai_hint' => null,
            ],
            13 => [
                'ai_mode' => null,
                'ai_voice_time' => null,
                'ai_hint' => null,
            ],
        ];

        $missing = [];

        foreach ($sections as $id => $data) {
            $section = SectionsInfo::find($id);

            if ($section === null) {
                $missing[] = $id;
                $this->command->warn("SectionsInfo row with id={$id} not found; skipping.");

                continue;
            }

            $section->update($data);
        }

        if (! empty($missing)) {
            $this->command->error('AiSectionSeeder: missing section ids: '.implode(', ', $missing));

            return;
        }

        $this->command->info('ai_mode, ai_hint, and ai_voice_time updated for all sections.');
    }
}
