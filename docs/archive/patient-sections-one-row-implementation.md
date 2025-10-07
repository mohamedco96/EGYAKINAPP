# Patient Sections - One Row Per Patient Implementation

## Overview

The Patient Sections Status page has been restructured to display **all patient sections in one row** per patient, providing a consolidated view of each patient's section completion status.

## Key Changes

### 🔄 Table Structure Transformation

#### **Before**: Multiple Rows Per Patient
- Each section had its own row
- Patient information repeated across multiple rows
- Difficult to get an overview of patient progress

#### **After**: One Row Per Patient
- Single row per patient showing all their sections
- All sections displayed with status indicators in one column
- Clear completion summary and progress overview

### 📊 New Column Layout

#### **1. Patient ID**
- Badge-styled with primary color
- Searchable and sortable
- Format: `#123`

#### **2. Patient Name** 
- Dynamically retrieved from first question answer
- Cached for performance
- Fallback: `Patient #ID`

#### **3. Assigned Doctor**
- Shows doctor name with tooltip
- Searchable functionality
- Placeholder for unassigned patients

#### **4. All Sections Status** (Main Feature)
- **Visual Badges**: Each section displayed as a colored badge
- **Status Indicators**: ✅ for completed, ❌ for pending
- **Color Coding**: 
  - Green background for completed sections
  - Orange background for pending sections
- **Responsive Layout**: Badges wrap to multiple lines as needed
- **Example Display**: 
  ```
  ✅ Patient History    ✅ Physical Exam    ❌ Lab Results    ❌ Assessment
  ```

#### **5. Completion Summary**
- **Percentage**: Overall completion rate
- **Fraction**: Completed/Total sections
- **Color Coding**: 
  - Green: ≥80% completion
  - Yellow: ≥50% completion  
  - Red: <50% completion
- **Format**: `75% (3/4 completed)`

#### **6. Last Activity**
- Shows most recent section update
- Relative time format ("2 hours ago")
- Tooltip with full timestamp

### ⚡ Enhanced Actions

#### **Row Actions**
1. **View Patient**: Direct link to patient details page
2. **Manage Sections**: Modal to toggle individual section statuses
3. **Complete All**: Mark all patient sections as completed

#### **Bulk Actions**
1. **Complete All Sections for Selected Patients**: Bulk complete all sections
2. **Mark All Sections as Pending**: Bulk mark sections as pending

### 🎯 Technical Implementation

#### **Query Optimization**
```php
protected static function getTableQuery(): Builder
{
    // Get unique patients who have section statuses
    return PatientStatus::query()
        ->select('patient_id')
        ->where('key', 'LIKE', 'section_%')
        ->with(['patient.doctor'])
        ->groupBy('patient_id')
        ->orderBy('patient_id', 'asc');
}
```

#### **All Sections Display Logic**
```php
Tables\Columns\TextColumn::make('all_sections_status')
    ->getStateUsing(function ($record) {
        return Cache::remember("patient_all_sections_{$record->patient_id}", 300, function () use ($record) {
            $sections = PatientStatus::where('patient_id', $record->patient_id)
                ->where('key', 'LIKE', 'section_%')
                ->orderBy('key')
                ->get();

            $sectionStatuses = [];
            foreach ($sections as $section) {
                $sectionId = str_replace('section_', '', $section->key);
                $sectionInfo = SectionsInfo::find($sectionId);
                $name = $sectionInfo?->section_name ?? "Section {$sectionId}";
                $status = $section->status ? '✅' : '❌';
                
                $sectionStatuses[] = [
                    'icon' => $status,
                    'name' => $name,
                    'status' => $section->status ? 'Completed' : 'Pending',
                    'completed' => $section->status
                ];
            }

            return $sectionStatuses;
        });
    })
    ->formatStateUsing(function ($state) {
        if (!is_array($state)) return 'No sections';
        
        $html = '<div class="space-y-1">';
        foreach ($state as $section) {
            $colorClass = $section['completed'] ? 'text-green-600 bg-green-50' : 'text-orange-600 bg-orange-50';
            $html .= '<div class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium ' . $colorClass . ' mr-1 mb-1">';
            $html .= '<span>' . $section['icon'] . '</span>';
            $html .= '<span>' . $section['name'] . '</span>';
            $html .= '</div>';
        }
        $html .= '</div>';
        
        return $html;
    })
    ->html()
    ->wrap()
```

### 🎨 Visual Design

#### **Section Badge Styling**
- **Completed Sections**: Green background with checkmark icon
- **Pending Sections**: Orange background with X icon
- **Responsive Design**: Badges wrap to new lines on smaller screens
- **Consistent Spacing**: Proper margins between badges

#### **Summary Display**
- **Centered Alignment**: Progress percentage prominently displayed
- **Color-Coded Text**: Visual indication of completion level
- **Compact Format**: Shows both percentage and fraction

### 📈 Benefits

#### **For Users**
- **Quick Overview**: See all patient sections at a glance
- **Easy Comparison**: Compare completion rates across patients
- **Efficient Management**: Bulk actions for multiple patients
- **Clear Status**: Visual indicators make status immediately obvious

#### **For Performance**
- **Reduced Rows**: Fewer table rows to render
- **Smart Caching**: Cached section data for faster loading
- **Optimized Queries**: Single query per patient instead of per section

#### **for Navigation**
- **Less Scrolling**: Fewer rows mean less vertical scrolling
- **Better Pagination**: More patients visible per page
- **Cleaner Interface**: Less cluttered, more organized view

## Usage Examples

### **Example Row Display**
```
Patient #123 | John Doe | Dr. Smith | ✅ History ✅ Exam ❌ Labs ❌ Assessment | 50% (2/4 completed) | 2 hours ago
```

### **Section Management Modal**
When clicking "Manage Sections":
- Shows all sections for the patient
- Toggle switches for each section
- Save changes updates all sections at once

### **Bulk Operations**
Select multiple patients and:
- Mark all their sections as completed
- Mark all their sections as pending
- Apply changes to all selected patients simultaneously

## ✅ Implementation Complete

The Patient Sections Status page now displays **all patient sections in one row**, providing:

- **Consolidated View**: One row per patient with all sections visible
- **Visual Status Indicators**: Clear badges showing completion status
- **Completion Summary**: Progress percentage and fraction
- **Efficient Management**: Individual and bulk section management
- **Performance Optimized**: Cached data and optimized queries

This implementation fulfills the requirement to show all patient sections in one row while maintaining full functionality and improving the overall user experience.
