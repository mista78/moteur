Sub viderRes()
    Dim feuille As String
    feuille = ActiveSheet.Name
    Sheets(feuille).Range("N10").Value = ""
    Sheets(feuille).Range("O10").Value = ""
    Sheets(feuille).Range("P10").Value = ""
    Sheets(feuille).Range("N13:O13").Value = ""
    Sheets(feuille).Range("N16").Value = ""
    Sheets(feuille).Range("F3:F33").Value = ""
    Sheets(feuille).Range("N19:O19").Value = ""
    Sheets(feuille).Range("N7").Value = ""
    Sheets(feuille).Range("R16").Value = ""
    Sheets(feuille).Range("R19").Value = ""
    Sheets(feuille).Range("R22").Value = ""
    Sheets(feuille).Range("S16").Value = ""
    Sheets(feuille).Range("S19").Value = ""
    Sheets(feuille).Range("S22").Value = ""
End Sub

Sub IJ()
    'récupère le nom de la feuille active
    Dim feuille As String
    feuille = ActiveSheet.Name
    
    Sheets(feuille).Cells(7, 14) = ageDate(Sheets(feuille).Cells(3, 14), Sheets(feuille).Cells(3, 11))
    
    'fusion des prolongations
    Dim i As Integer
    i = 50
    Dim j As Integer
    j = 1
    Dim k As Integer
    Do While i > 4
        If Sheets(feuille).Cells(i, 1) = Application.WorksheetFunction.WorkDay(Sheets(feuille).Cells(i - 1, 2), 1) Then
            Sheets(feuille).Cells(i - 1, 2) = Sheets(feuille).Cells(i, 2)
            For k = i To 49
                For j = 1 To 6
                    Sheets(feuille).Cells(k, j) = Sheets(feuille).Cells(k + 1, j)
                Next j
            Next k
            For j = 1 To 6
                Sheets(feuille).Cells(50, j) = ""
            Next j
        End If
        i = i - 1
    Loop
    

    datesEffetsDroits (feuille)
    Dim montant As Double
    Dim nbJours As Integer
    'Cells(Ligne, colonne)
    'If Sheets(feuille).Cells(24, 3) < Sheets(feuille).Cells(24, 4) Then MsgBox "oui"
    If Not (IsEmpty(Sheets(feuille).Cells(3, 15))) And Sheets(feuille).Cells(3, 15) = "RSPM" Then Sheets(feuille).Cells(3, 9) = "A"
    
    'Si moins de 8 trimestres d'affiliation, pas d'IJ
    If (Not IsEmpty(Sheets(feuille).Cells(3, 19))) And Sheets(feuille).Cells(3, 19) >= 8 Then
        nbJours = calculNbJours(feuille)
    Else
        nbJours = 0
    End If
    
    If nbJours < 0 Then nbJours = 0
    'Plus de 3 ans d'indemnité => 0 jours d'IJ
    If (Not IsEmpty(Sheets(feuille).Cells(16, 8))) And Sheets(feuille).Cells(16, 8) > 1095 Then nbJours = 0
    
    Dim cumulJoursAnciens As Integer
    
    If IsEmpty(Sheets(feuille).Cells(16, 8)) Then
        cumulJoursAnciens = 0
    Else
        cumulJoursAnciens = Sheets(feuille).Cells(16, 8)
    End If
    
    If ageDate(Sheets(feuille).Cells(3, 14), Sheets(feuille).Cells(3, 11)) >= 70 Then
        If nbJours + cumulJourAnciens > 365 Then
            nbJours = 365 - cumulJoursAnciens
            If nbJours < 0 Then nbJours = 0
        End If
    End If
    
    
    
    Dim classeCotisation As String
    Dim optionCotisation As String
    classeCotisation = UCase(Sheets(feuille).Cells(3, 9).Value)
    optionCotisation = Sheets(feuille).Cells(3, 10)
    Dim montantRattrap As Double
    montantRattrap = 0
    If Not IsEmpty(Sheets(feuille).Cells(19, 8)) And UCase(Sheets(feuille).Cells(19, 8)) <> UCase(Sheets(feuille).Cells(3, 9).Value) Then
         'Gerer Rattrapage pour changement de classe de cotisation
         montantRattrap = gestionChangeClasse(feuille)
         Sheets(feuille).Cells(19, 14) = montantRattrap
    End If
    
    Sheets(feuille).Cells(10, 14) = nbJours
    montant = calculMontant(feuille, nbJours, cumulJoursAnciens, classeCotisation, optionCotisation)
    
    
    
    'Si le taux est forcé, renvoie le taux forcé
    If Not IsEmpty(Sheets(feuille).Cells(13, 8)) Then
        montant = Sheets(feuille).Cells(13, 8)
    End If
    
    
    If IsEmpty(Sheets(feuille).Cells(22, 8)) Then
        Sheets(feuille).Cells(13, 14).Value = montant + montantRattrap
    Else
        Sheets(feuille).Cells(13, 14).Value = Sheets(feuille).Cells(22, 8) * (montant + montantRattrap)
    End If
    'nbJoursTotaux
    Sheets(feuille).Cells(16, 14).Value = Sheets(feuille).Cells(16, 8).Value + nbJours
End Sub

Function ageDate(jour As Date, anniv As Date) As Integer
    'anniversaire passé ?
    
    
    If Month(anniv) < Month(jour) Then
        ageDate = Year(jour) - Year(anniv)
    'anniversaire à venir
    ElseIf Month(anniv) = Month(jour) Then
        If Day(anniv) < Day(jour) Then
            ageDate = Year(jour) - Year(anniv)
        Else
            ageDate = Year(jour) - Year(anniv) - 1
        End If
         
    ElseIf Month(anniv) > Month(jour) Then
        ageDate = Year(jour) - Year(anniv) - 1
    End If
    
    
End Function

Function gestionChangeClasse(feuille As String) As Double
    
    
    'Gestion des taux par annee
    
    Dim montantAncienneClasse As Double
    Dim montantNouvelleClasse As Double
    Dim optionCotisation As String
    optionCotisation = Sheets(feuille).Cells(3, 10).Value
    Dim ancienneClasse As String
    Dim nouvelleClasse As String
    Dim nbJoursRattrap As Integer
    
    ancienneClasse = Sheets(feuille).Cells(19, 8)
    nouvelleClasse = Sheets(feuille).Cells(3, 9)
    nbJoursRattrap = Sheets(feuille).Cells(16, 8)
'        montantAncienneClasse = calculMontant(feuille, nbJoursRattrap, 0, ancienneClasse, "0,25")
        montantAncienneClasse = calculMontant(feuille, nbJoursRattrap, 0, ancienneClasse, optionCotisation)
'        montantNouvelleClasse = calculMontant(feuille, nbJoursRattrap, 0, nouvelleClasse, "0,25")
        montantNouvelleClasse = calculMontant(feuille, nbJoursRattrap, 0, nouvelleClasse, optionCotisation)
    'Montées
    gestionChangeClasse = (montantNouvelleClasse - montantAncienneClasse)
    
    
    
End Function


Function datesEffetsDroits(feuille As String)

    
    Dim i, nbJours, nbArrets As Integer
    nbJours = 0
    nbArrets = 1
    i = 3
    
    Dim nbJoursPlus, j As Integer
    Dim dateAjout As Date
    
    Dim arrets(50, 3) As Variant
    
    Dim arretDroit As Integer
    Dim dateFinArretDroit As Date
    arretDroit = 0
    Dim dateCotis As Date
    Dim dateDT As Date

    Dim nbJoursCumul As Integer
    Dim a1 As Boolean
    Dim a2 As Boolean
    Dim a3 As Boolean
    a1 = False
    a2 = False
    a3 = False
    If Not (IsEmpty(Sheets(feuille).Cells(16, 8))) Then
        nbJoursCumul = Sheets(feuille).Cells(16, 8)
    Else
        nbJoursCumul = 0
    End If
    

    
    'Parcours la liste des arrêts de travail, jusqu'à 50 arrêts possibles
    Do While Not IsEmpty(Sheets(feuille).Cells(i, 1)) Or i = 50


        'Date de DEBUT de l'arrêt de travail i
        arrets(nbArrets, 1) = Sheets(feuille).Cells(i, 1)
        'Date de FIN de l'arrêt de travail i
        arrets(nbArrets, 2) = Sheets(feuille).Cells(i, 2)
        'Duree de l'arrêt de travail i
        arrets(nbArrets, 3) = DateDiff("d", Sheets(feuille).Cells(i, 1), Sheets(feuille).Cells(i, 2)) + 1
        'MsgBox arrets(nbArrets, 2)
        'nombre de jours des arrêts cumulés
        nbJours = nbJours + arrets(nbArrets, 3)
    
        'Si la date de debut de droit est forcée, ne pas la calculer
        If Not IsEmpty(Sheets(feuille).Cells(i, 6)) Then
            GoTo finBoucle
        End If
        
        If nbJours <= 90 Then GoTo finBoucle
        
        If nbJours > 90 And arretDroit = 0 Then
            arretDroit = i
            Sheets(feuille).Cells(i, 6) = DateAdd("d", (90 - (nbJours - arrets(nbArrets, 3))), Sheets(feuille).Cells(i, 1).Value)
        
            If Not (IsEmpty(Sheets(feuille).Cells(i, 3))) And Sheets(feuille).Cells(i, 3) = "N" Then
                'MsgBox "DT non excusée : " & Sheets(feuille).Cells(i, 4)
                
                MsgBox Sheets(feuille).Cells(i, 4)
                dateDT = DateAdd("d", 31, Sheets(feuille).Cells(i, 4))
                MsgBox dateDT
            End If
            If Not (IsEmpty(Sheets(feuille).Cells(i, 7))) And Sheets(feuille).Cells(i, 7) = "O" Then
                'MsgBox "Compte cotisant mis à jour le : " & Sheets(feuille).Cells(i, 8)
                 
                dateCotis = DateAdd("d", 31, Sheets(feuille).Cells(i, 8))
            End If
            Sheets(feuille).Cells(i, 6) = CDate(Application.WorksheetFunction.Max(Sheets(feuille).Cells(i, 6), dateDT, dateCotis))
            dateFinArretDroit = arrets(nbArrets, 2)
            GoTo finBoucle
        End If
        

        'Si on dépasse 90 jours d'arrêt cumulés
        If nbJours > 90 And arretDroit <> 0 Then
            
            MsgBox "enter"
        
            'MsgBox dateFinArretDroit
            'Si nouvel arrêt consécutif
            If DateDiff("d", arrets(nbArrets - 1, 2), arrets(nbArrets, 1)) = 1 Then
                If Not (IsEmpty(Sheets(feuille).Cells(i, 3))) And Sheets(feuille).Cells(i, 3) = "N" Then
                    'MsgBox "DT non excusée : " & Sheets(feuille).Cells(i, 4)
                    dateDT = DateAdd("d", 31, Sheets(feuille).Cells(i, 4))
                End If
               
                If Not (IsEmpty(Sheets(feuille).Cells(i, 7))) And Sheets(feuille).Cells(i, 7) = "O" Then
                    'MsgBox "Compte cotisant mis à jour le : " & Sheets(feuille).Cells(i, 8)
                    
                    dateCotis = DateAdd("d", 31, Sheets(feuille).Cells(i, 8))
                End If
                'MsgBox "Date debut arret : " & Sheets(feuille).Cells(i, 1) _
                & " | Date DT non excusée : " & dateDT _
                & " | Date Maj Cotis : " & dateCotis _
                & "maximale : " & CDate(Application.WorksheetFunction.Max(arrets(nbArrets, 1), dateDT, dateCotis))
                Sheets(feuille).Cells(i, 6) = CDate(Application.WorksheetFunction.Max(arrets(nbArrets, 1), dateDT, dateCotis))
                If Sheets(feuille).Cells(i, 6) > arrets(nbArrets, 2) Then Sheets(feuille).Cells(i, 6).Value = ""
                dateFinArretDroit = arrets(nbArrets, 2)
                GoTo finBoucle
            'Rechute avec démarrage au 1er jour
            ElseIf Sheets(feuille).Cells(i, 5) = "O" Then
                'MsgBox "rechute premier jour"
                Sheets(feuille).Cells(i, 6) = arrets(nbArrets, 1)
                dateFinArretDroit = arrets(nbArrets, 2)
                GoTo finBoucle
            'Rechute avec démarrage au 15ème jour
            Else
                'MsgBox "rechute 15eme jour"
                If DateDiff("d", arrets(nbArrets, 1), arrets(nbArrets, 2)) > 15 Then
                    Sheets(feuille).Cells(i, 6) = DateAdd("d", 14, arrets(nbArrets, 1))
                    dateFinArretDroit = arrets(nbArrets, 2)
                    GoTo finBoucle
                End If
            End If
        End If
finBoucle:
        

        nbJoursCumul = nbJoursCumul + arrets(nbArrets, 3)
        i = i + 1
        nbArrets = nbArrets + 1
    Loop
    
End Function

'Renvoie l'arret de travail de la date d'effet des droits aux IJ
Function arretDebutDroit(feuille As String) As Integer

    Dim i, nbJours As Integer
    nbJours = 0
    arretDebutDroit = 1
    i = 3
    Dim arrets(50) As Integer
    Dim arretDroit As Integer
    Do While Not IsEmpty(Sheets(feuille).Cells(i, 1))
        nbJours = nbJours + arrets(nbArrets)
        'Cas non rechute
        If UCase(Sheets(feuille).Cells(i, 5)) = "N" And nbJours > 90 Then Exit Function
        'Cas de rechute
        '1 - Rechute avec droits au premier jour
        If UCase(Sheets(feuille).Cells(i, 5)) = "O" And UCase(Sheets(feuille).Cells(7, 7)) = "O" Then Exit Function
        '2 - Rechute avec droits au 15ème jour
        If UCase(Sheets(feuille).Cells(i, 5)) = "O" And nbJours > 15 And UCase(Sheets(feuille).Cells(7, 7)) = "N" Then Exit Function
        i = i + 1
        arretDebutDroit = arretDebutDroit + 1
    Loop
End Function

Function calculNbJours(feuille As String) As Integer

    calculNbJours = 0
    Dim i As Integer
    Dim rechutes As Integer
    Dim nbArrets As Integer
    Dim dateEffet As Date
    'dateEffet = datesEffetsDroits(feuille)
    'Sheets(feuille).Cells(10, 16).Value = CDate(dateEffet)
    
    Dim joursAjout As Integer
    

    Dim dateAttestation As Date
      
    'Nombre de jours = de la date d'effet des droits à la fin du mois en cours
    'Sheets(feuille).Cells(3,16) => Date du dernier paiement
    'Sheets(feuille).Cells(3,12) => Date du jour actuel

    
    Dim datePaiement As Date
    datePaiement = Sheets(feuille).Cells(3, 16)

    Dim dateJour As Date
    dateJour = Sheets(feuille).Cells(3, 14)

    Dim dateDebAjout As Date
    
    
    'Si il n'y a pas d'attestation, pas de paiement
    If IsEmpty(Sheets(feuille).Cells(3, 12)) Then
        Exit Function
    Else
        dateAttestation = Sheets(feuille).Cells(3, 12)
    End If
    
    Dim dateFin, dateDebut As Date
    
    i = 3
    Do While Not IsEmpty(Sheets(feuille).Cells(i, 1)) Or i = 50
        
        If Not (IsEmpty(Sheets(feuille).Cells(i, 6))) Then
        dateDebut = Sheets(feuille).Cells(i, 6)
        
        If datePaiement > dateDebut And datePaiement < dateFin Then
            dateDebut = datePaiement
        End If
        
        If datePaiement < dateDebut Then
            datePaiement = dateDebut
        End If
        
        dateFin = Sheets(feuille).Cells(i, 2)
                    
        If Day(dateAttestation) >= 27 Then
            dateAttestation = CDate(Application.WorksheetFunction.EoMonth(dateAttestation, 0))
        End If
        'MsgBox datePaiement
        If datePaiement < dateFin And datePaiement < dateAttestation Then
            'MsgBox "ici"
                If dateAttestation > dateDebut Then
                    If dateAttestation < dateFin Then
                        'MsgBox "if : " & DateDiff("d", datePaiement, dateFin)
                        calculNbJours = calculNbJours + DateDiff("d", datePaiement, dateAttestation) + 1
                    Else
                        'MsgBox "else : " & DateDiff("d", datePaiement, dateFin)
                        calculNbJours = calculNbJours + DateDiff("d", datePaiement, dateFin) + 1
                    End If
                End If
            End If
        End If
        i = i + 1
    Loop

End Function



Function calculMontant(feuille As String, nbJours As Integer, cumulJoursAnciens As Integer, classeCotis As String, optionCotis As String) As Double
    'Sheets(feuille).Cells(10, 15) = ""
    
    Dim nbJoursAPayer As Integer
    nbJoursAPayer = nbJours
    calculMontant = 0
    
    Dim classeCotisation As String
    Dim optionCotisation As String
    'classeCotisation = UCase(Sheets(feuille).Cells(3, 9).Value)
    'optionCotisation = Sheets(feuille).Cells(3, 10)
    classeCotisation = classeCotis
    optionCotisation = optionCotis
    
    Dim cumulNbJours As Integer
    cumulNbJours = cumulJoursAnciens

    
    Dim taux As Integer
    Dim age As Integer
    Dim nbTrimestresAffiliation As Integer
    
    Dim statut As String
    statut = Sheets(feuille).Cells(3, 15)
    
    
    Dim pathoAnt As String
    Dim dateAffiliation As Date
    Dim tauxPathoAnt As Integer
    
    pathoAnt = Sheets(feuille).Cells(3, 17)
    dateAffiliation = Sheets(feuille).Cells(3, 18)
    nbTrimestresAffiliation = Sheets(feuille).Cells(3, 19)
    tauxPathoAnt = 0
    
    If UCase(pathoAnt) = "O" Then
        If nbTrimestresAffiliation <= 15 And nbTrimestresAffiliation > 7 Then
            tauxPathoAnt = 2
        ElseIf datePatho < dateAffiliation And nbTrimestresAffiliation <= 23 And nbTrimestresAffiliation > 15 Then
            tauxPathoAnt = 1
        End If
            
    End If
    
    
    Dim dateAnniversaire As Date
    dateAnniversaire = Sheets(feuille).Cells(3, 11)
    
    age = ageDate(Sheets(feuille).Cells(3, 14), dateAnniversaire)
    
    Dim pass62 As Boolean
    
    pass62 = False
    
    
    
    '-------------- 61-
    Select Case age
    Case Is < 62
        'ageDate(
        If UCase(pathoAnt) = "O" Then
        
            If datePatho < dateAffiliation And nbTrimestresAffiliation <= 15 And nbTrimestresAffiliation > 7 Then
                taux = 3
            ElseIf datePatho < dateAffiliation And nbTrimestresAffiliation <= 23 And nbTrimestresAffiliation > 15 Then
                taux = 2
            Else
                taux = 1
            End If
        Else
            taux = 1
        End If
        
        '----------------------- 62 à 69
    Case 62 To 69
        Dim anneeCot As Integer
        If UCase(pathoAnt) = "O" And nbTrimestresAffiliation <= 15 And nbTrimestresAffiliation > 7 Then
            taux = 1
        ElseIf UCase(pathoAnt) = "O" And nbTrimestresAffiliation <= 23 And nbTrimestresAffiliation > 15 Then
            taux = 2
        Else
            taux = 0
        End If
        
        Dim lim1, lim2, lim3, decompteJours As Integer
        decompteJours = cumulNbJours
        lim1 = 365
        lim2 = 730
        lim3 = 1095
        
        Dim NbJoursA1, NbJoursA2, NbJoursA3 As Integer
        
        NbJoursA1 = 0
        NbJoursA2 = 0
        NbJoursA3 = 0
        
        If decompteJours + nbJoursAPayer <= lim1 Then
            NbJoursA1 = nbJoursAPayer
            calculMontant = NbJoursA1 * selectStatut(statut, classeCotisation, optionCotisation, 1 + taux, feuille)
            If InStr(Sheets(feuille).Cells(10, 15).Value, "A1") = 0 Then
                Sheets(feuille).Cells(10, 15).Value = Sheets(feuille).Cells(10, 15).Value & " | " & selectStatut(statut, classeCotisation, optionCotisation, 1 + taux, feuille)
                Sheets(feuille).Cells(16, 19).Value = "Taux " & taux + 1 & " vers Taux " & taux + 7
            End If
            Exit Function
        ElseIf decompteJours + nbJoursAPayer > lim1 And decompteJours < lim1 Then
            NbJoursA1 = lim1 - decompteJours
        End If
        nbJoursAPayer = nbJoursAPayer - NbJoursA1
        decompteJours = decompteJours + NbJoursA1
        
        calculMontant = calculMontant + (NbJoursA1 * selectStatut(statut, classeCotisation, optionCotisation, 1 + taux, feuille))
        'msgbox "NbJoursA1 : " & NbJoursA1 & " | Calcul Montant : " & calculMontant
        If calculMontant <> 0 Then
            If InStr(Sheets(feuille).Cells(10, 15).Value, "A1") = 0 Then
                Sheets(feuille).Cells(10, 15).Value = Sheets(feuille).Cells(10, 15).Value & " | " & "A1 : " & selectStatut(statut, classeCotisation, optionCotisation, 1 + taux, feuille)
                Sheets(feuille).Cells(16, 19).Value = "Taux " & taux + 1 & " vers Taux " & taux + 7
            End If
        End If
        
        
        
        
        If decompteJours + nbJoursAPayer <= lim2 Then
            NbJoursA2 = nbJoursAPayer
            calculMontant = calculMontant + (NbJoursA2 * selectStatut(statut, classeCotisation, optionCotisation, 7 + taux, feuille))
            If InStr(Sheets(feuille).Cells(10, 15).Value, "A2") = 0 Then
                Sheets(feuille).Cells(10, 15).Value = Sheets(feuille).Cells(10, 15).Value & " | A2 : " & selectStatut(statut, classeCotisation, optionCotisation, 7 + taux, feuille)
                Sheets(feuille).Cells(19, 19).Value = "Taux " & taux + 7 & " vers Taux " & taux + 4
            End If
            Exit Function
        ElseIf decompteJours + nbJoursAPayer > lim2 And decompteJours < lim2 Then
            NbJoursA2 = lim2 - decompteJours
        End If
        nbJoursAPayer = nbJoursAPayer - NbJoursA2
        decompteJours = decompteJours + NbJoursA2
        
        calculMontant = calculMontant + (NbJoursA2 * selectStatut(statut, classeCotisation, optionCotisation, 7 + taux, feuille))
        If calculMontant <> 0 Then
            If InStr(Sheets(feuille).Cells(10, 15).Value, "A2") = 0 Then
                Sheets(feuille).Cells(10, 15).Value = Sheets(feuille).Cells(10, 15).Value & " | A2 : " & selectStatut(statut, classeCotisation, optionCotisation, 7 + taux, feuille)
                Sheets(feuille).Cells(19, 19).Value = "Taux " & taux + 7 & " vers Taux " & taux + 4
            End If
        End If
        
        
        If decompteJours + nbJoursAPayer <= lim3 Then
            NbJoursA3 = nbJoursAPayer
            calculMontant = calculMontant + (NbJoursA3 * selectStatut(statut, classeCotisation, optionCotisation, 4 + taux, feuille))
            If InStr(Sheets(feuille).Cells(10, 15).Value, "A3") = 0 Then
                Sheets(feuille).Cells(10, 15).Value = Sheets(feuille).Cells(10, 15).Value & " | A3 : " & selectStatut(statut, classeCotisation, optionCotisation, 4 + taux, feuille)
                'Sheets(feuille).Cells(22, 19).Value = "Fin du taux " & taux + 4
                End If
            Exit Function
        ElseIf decompteJours + nbJoursAPayer > lim3 And decompteJours < lim3 Then
            NbJoursA3 = lim3 - decompteJours
        End If
        nbJoursAPayer = nbJoursAPayer - NbJoursA3
        decompteJours = decompteJours + NbJoursA3
        
        calculMontant = calculMontant + (NbJoursA3 * selectStatut(statut, classeCotisation, optionCotisation, 4 + taux, feuille))
        If calculMontant <> 0 Then
            If InStr(Sheets(feuille).Cells(10, 15).Value, "A1") = 0 Then
                Sheets(feuille).Cells(10, 15).Value = Sheets(feuille).Cells(10, 15).Value & " | A3 : " & selectStatut(statut, classeCotisation, optionCotisation, 4 + taux, feuille)
                'Sheets(feuille).Cells(22, 19).Value = "Fin du taux " & taux + 4
            End If
        End If
        Exit Function
        
        
        
        '------- 70+
    Case Is > 69
        If UCase(pathoAnt) = "O" And nbTrimestresAffiliation <= 15 And nbTrimestresAffiliation > 7 Then
            taux = 6
        ElseIf UCase(pathoAnt) = "O" And nbTrimestresAffiliation <= 23 And nbTrimestresAffiliation > 15 Then
            taux = 5
        Else
            taux = 4
        End If
        
        
    End Select
    
    calculMontant = nbJoursAPayer * selectStatut(statut, classeCotisation, optionCotisation, taux, feuille)
    
    Sheets(feuille).Cells(10, 15).Value = Sheets(feuille).Cells(10, 15).Value & " | " & selectStatut(statut, classeCotisation, optionCotisation, taux, feuille)
End Function

Function selectStatut(statut As String, classeCotisation As String, optionCotisation As String, taux As Integer, feuille As String) As Double
    
    Dim annee As Integer
    Dim i As Integer
    
    i = 3
    Do While Not IsEmpty(Sheets(feuille).Cells(i, 1)) Or i = 50
        If Sheets(feuille).Cells(i, 6) <> 0 Then
            annee = Year(Sheets(feuille).Cells(i, 6))
            If ageDate(Sheets(feuille).Cells(i, 6), Sheets(feuille).Cells(3, 11)) >= 70 Then
                Sheets(feuille).Cells(16, 18) = DateAdd("d", (365 - Sheets(feuille).Cells(16, 8)), Sheets(feuille).Cells(i, 6)) - 1
            Else
                If ageDate(Sheets(feuille).Cells(i, 6), Sheets(feuille).Cells(3, 11)) >= 62 Then
                    Sheets(feuille).Cells(16, 18) = DateAdd("d", (365 - Sheets(feuille).Cells(16, 8)), Sheets(feuille).Cells(i, 6)) - 1
                    Sheets(feuille).Cells(19, 18) = DateAdd("d", (730 - Sheets(feuille).Cells(16, 8)), Sheets(feuille).Cells(i, 6)) - 1
                    Sheets(feuille).Cells(22, 18) = DateAdd("d", (1095 - Sheets(feuille).Cells(16, 8)), Sheets(feuille).Cells(i, 6)) - 1
                End If
            End If
            
            Exit Do
        End If
        i = i + 1
    Loop
    
    Select Case statut
    Case "M"
        selectStatut = medecinTaux(classeCotisation, taux, annee)
    Case "RSPM"
        selectStatut = RSPMTaux(optionCotisation, taux, annee)
    Case "CCPL"
        selectStatut = CCPLTaux(classeCotisation, optionCotisation, taux, annee)
    Case Else
        MsgBox "Statut non supporté"
        Exit Function
    End Select
    
End Function


Function medecinTaux(classe As String, tauxPassage As Integer, annee As Integer) As Double
    Dim anneeLigne As Integer
    Dim taux As Integer
    
    anneeLigne = 0
    taux = tauxPassage + 3
    
    If annee = 2023 Then anneeLigne = 13
    If annee = 2022 Then anneeLigne = 26
    
    Select Case UCase(classe)
    Case "A"
        medecinTaux = Sheets("Taux médecins").Cells(taux + anneeLigne, 3)
    Case "B"
        medecinTaux = Sheets("Taux médecins").Cells(taux + anneeLigne, 4)
    Case "C"
        medecinTaux = Sheets("Taux médecins").Cells(taux + anneeLigne, 5)
    Case Else
        MsgBox "Classe non prise en charge pour les médecins"
        End
    End Select
    
End Function

Function CCPLTaux(classe As String, optionPourcent As String, tauxPassage As Integer, annee As Integer) As Double
    Dim Plus As Integer
    Dim annee As Integer
    Dim taux As Integer
    
    Select Case optionPourcent
    Case "0,25"
        Plus = 0
    Case "0,5"
        Plus = 1
    Case Else
        MsgBox "Taux non pris en charge pour les CCPL"
        End
    End Select
    
    annee = 0
    taux = tauxPassage + 4
    
    Select Case UCase(classe)
    Case "A"
        CCPLTaux = Sheets("Taux CCPL").Cells(taux + annee, 3 + Plus)
    Case "B"
        CCPLTaux = Sheets("Taux CCPL").Cells(taux + annee, 5 + Plus)
    Case "C"
        CCPLTaux = Sheets("Taux CCPL").Cells(taux + annee, 7 + Plus)
    Case Else
        MsgBox "Classe non prise en charge pour les CCPL"
        End
    End Select
End Function


Function RSPMTaux(optionPourcent As String, tauxPassage As Integer, annee As Integer) As Double
    Dim annee As Integer
    Dim taux As Integer
    
    annee = 0
    taux = tauxPassage + 4
    Select Case optionPourcent
    Case "0,25"
        RSPMTaux = Sheets("Taux RSPM").Cells(taux, 3)
    Case "1"
        RSPMTaux = Sheets("Taux RSPM").Cells(taux, 4)
    Case Else
        MsgBox "Taux non pris en charge pour les RSPM"
        End
    End Select
End Function

